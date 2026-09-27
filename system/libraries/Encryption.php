<?php
declare(strict_types=1);
/**
 * CodeIgniter
 *
 * An open source application development framework for PHP
 *
 * This content is released under the MIT License (MIT)
 *
 * Copyright (c) 2019 - 2022, CodeIgniter Foundation
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 *
 * @package	CodeIgniter
 * @author	EllisLab Dev Team
 * @copyright	Copyright (c) 2008 - 2014, EllisLab, Inc. (https://ellislab.com/)
 * @copyright	Copyright (c) 2014 - 2019, British Columbia Institute of Technology (https://bcit.ca/)
 * @copyright	Copyright (c) 2019 - 2022, CodeIgniter Foundation (https://codeigniter.com/)
 * @license	https://opensource.org/licenses/MIT	MIT License
 * @link	https://github.com/imamrasyid/customigniter-3
 * @since	Version 3.0.0
 * @filesource
 */
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * CodeIgniter Encryption Class
 *
 * Provides two-way keyed encryption via PHP's OpenSSL extension.
 *
 * @package		CodeIgniter
 * @subpackage	Libraries
 * @category	Libraries
 * @author		Andrey Andreev
 * @link		https://codeigniter.com/userguide3/libraries/encryption.html
 */
#[\AllowDynamicProperties]
class CI_Encryption {

	/**
	 * Encryption cipher
	 *
	 * @var	string
	 */
	protected $_cipher = 'aes-128';

	/**
	 * Cipher mode
	 *
	 * @var	string
	 */
	protected $_mode = 'cbc';

	/**
	 * Cipher handle
	 *
	 * @var	mixed
	 */
	protected $_handle;

	/**
	 * Encryption key
	 *
	 * @var	string
	 */
	protected $_key;

	/**
	 * PHP extension to be used
	 *
	 * @var	string
	 */
	protected $_driver;

	/**
	 * List of usable drivers (PHP extensions)
	 *
	 * @var	array
	 */
	protected $_drivers = array();

	/**
	 * List of available modes
	 *
	 * @var	array
	 */
	protected $_modes = array(
		'openssl' => array(
			'cbc' => 'cbc',
			'ecb' => 'ecb',
			'ofb' => 'ofb',
			'cfb' => 'cfb',
			'cfb8' => 'cfb8',
			'ctr' => 'ctr',
			'stream' => '',
			'xts' => 'xts',
			'gcm' => 'gcm'
		)
	);

	/**
	 * List of supported HMAC algorithms
	 *
	 * name => digest size pairs
	 *
	 * @var	array
	 */
	protected $_digests = array(
		'sha224' => 28,
		'sha256' => 32,
		'sha384' => 48,
		'sha512' => 64
	);

	// --------------------------------------------------------------------

	/**
	 * Class constructor
	 *
	 * @param	array	$params	Configuration parameters
	 * @return	void
	 */
	public function __construct(array $params = array())
	{
		if ( ! extension_loaded('openssl'))
		{
			show_error('Encryption: Unable to find an available encryption driver (OpenSSL).');
		}

		$this->_drivers = array('openssl' => TRUE);

		$this->initialize($params);

		if ( ! isset($this->_key) && self::strlen($key = config_item('encryption_key')) > 0)
		{
			$this->_key = $key;
		}

		log_message('info', 'Encryption Class Initialized');
	}

	// --------------------------------------------------------------------

	/**
	 * Initialize
	 *
	 * @param	array	$params	Configuration parameters
	 * @return	CI_Encryption
	 */
	public function initialize(array $params)
	{
		if ( ! empty($params['driver']))
		{
			if (isset($this->_drivers[$params['driver']]))
			{
				if ($this->_drivers[$params['driver']])
				{
					$this->_driver = $params['driver'];
				}
				else
				{
					log_message('error', "Encryption: Driver '".$params['driver']."' is not available.");
				}
			}
			else
			{
				log_message('error', "Encryption: Unknown driver '".$params['driver']."' cannot be configured.");
			}
		}

		if (empty($this->_driver))
		{
			$this->_driver = 'openssl';

			log_message('debug', "Encryption: Auto-configured driver '".$this->_driver."'.");
		}

		empty($params['cipher']) && $params['cipher'] = $this->_cipher;
		empty($params['key']) OR $this->_key = $params['key'];
		$this->{'_'.$this->_driver.'_initialize'}($params);
		return $this;
	}

	// --------------------------------------------------------------------

	/**
	 * Initialize OpenSSL
	 *
	 * @param	array	$params	Configuration parameters
	 * @return	void
	 */
	protected function _openssl_initialize($params)
	{
		if ( ! empty($params['cipher']))
		{
			$params['cipher'] = strtolower($params['cipher']);
			$this->_cipher_alias($params['cipher']);
			$this->_cipher = $params['cipher'];
		}

		if ( ! empty($params['mode']))
		{
			$params['mode'] = strtolower($params['mode']);
			if ( ! isset($this->_modes['openssl'][$params['mode']]))
			{
				log_message('error', 'Encryption: OpenSSL mode '.strtoupper($params['mode']).' is not available.');
			}
			else
			{
				$this->_mode = $this->_modes['openssl'][$params['mode']];
			}
		}

		if (isset($this->_cipher, $this->_mode))
		{
			// This is mostly for the stream mode, which doesn't get suffixed in OpenSSL
			$handle = empty($this->_mode)
				? $this->_cipher
				: $this->_cipher.'-'.$this->_mode;

			if ( ! in_array($handle, openssl_get_cipher_methods(), TRUE))
			{
				$this->_handle = NULL;
				log_message('error', 'Encryption: Unable to initialize OpenSSL with method '.strtoupper($handle).'.');
			}
			else
			{
				$this->_handle = $handle;
				log_message('info', 'Encryption: OpenSSL initialized with method '.strtoupper($handle).'.');
			}
		}
	}

	// --------------------------------------------------------------------

	/**
	 * Create a random key
	 *
	 * @param	int	$length	Output length
	 * @return	string
	 */
	public function create_key($length)
	{
		try
		{
			return random_bytes((int) $length);
		}
		catch (Exception $e)
		{
			log_message('error', $e->getMessage());
			return FALSE;
		}
	}

	// --------------------------------------------------------------------

	/**
	 * Encrypt
	 *
	 * @param	string	$data	Input data
	 * @param	array	$params	Input parameters
	 * @return	string
	 */
	public function encrypt($data, ?array $params = null)
	{
		if (($params = $this->_get_params($params)) === FALSE)
		{
			return FALSE;
		}

		isset($params['key']) OR $params['key'] = $this->hkdf($this->_key, 'sha512', NULL, self::strlen($this->_key), 'encryption');

		if (($data = $this->{'_'.$this->_driver.'_encrypt'}($data, $params)) === FALSE)
		{
			return FALSE;
		}

		$params['base64'] && $data = base64_encode($data);

		if (isset($params['hmac_digest']))
		{
			isset($params['hmac_key']) OR $params['hmac_key'] = $this->hkdf($this->_key, 'sha512', NULL, NULL, 'authentication');
			return hash_hmac($params['hmac_digest'], $data, $params['hmac_key'], ! $params['base64']).$data;
		}

		return $data;
	}

	// --------------------------------------------------------------------

	/**
	 * Encrypt via OpenSSL
	 *
	 * @param	string	$data	Input data
	 * @param	array	$params	Input parameters
	 * @return	string
	 */
	protected function _openssl_encrypt($data, $params)
	{
		if (empty($params['handle']))
		{
			return FALSE;
		}

		$iv = ($iv_size = openssl_cipher_iv_length($params['handle']))
			? $this->create_key($iv_size)
			: '';

		// GCM mode requires an authentication tag
		if (substr($params['handle'], -4) === '-gcm')
		{
			$tag = '';
			$data = openssl_encrypt(
				$data,
				$params['handle'],
				$params['key'],
				OPENSSL_RAW_DATA,
				$iv,
				$tag,
				16
			);

			if ($data === FALSE)
			{
				return FALSE;
			}

			return $iv.$tag.$data;
		}

		$data = openssl_encrypt(
			$data,
			$params['handle'],
			$params['key'],
			OPENSSL_RAW_DATA,
			$iv
		);

		if ($data === FALSE)
		{
			return FALSE;
		}

		return $iv.$data;
	}

	// --------------------------------------------------------------------

	/**
	 * Decrypt
	 *
	 * @param	string	$data	Encrypted data
	 * @param	array	$params	Input parameters
	 * @return	string
	 */
	public function decrypt($data, ?array $params = null)
	{
		if (($params = $this->_get_params($params)) === FALSE)
		{
			return FALSE;
		}

		if (isset($params['hmac_digest']))
		{
			// This might look illogical, but it is done during encryption as well ...
			// The 'base64' value is effectively an inverted "raw data" parameter
			$digest_size = ($params['base64'])
				? $this->_digests[$params['hmac_digest']] * 2
				: $this->_digests[$params['hmac_digest']];

			if (self::strlen($data) <= $digest_size)
			{
				return FALSE;
			}

			$hmac_input = self::substr($data, 0, $digest_size);
			$data = self::substr($data, $digest_size);

			isset($params['hmac_key']) OR $params['hmac_key'] = $this->hkdf($this->_key, 'sha512', NULL, NULL, 'authentication');
			$hmac_check = hash_hmac($params['hmac_digest'], $data, $params['hmac_key'], ! $params['base64']);

			// Time-attack-safe comparison
			$diff = 0;
			for ($i = 0; $i < $digest_size; $i++)
			{
				$diff |= ord($hmac_input[$i]) ^ ord($hmac_check[$i]);
			}

			if ($diff !== 0)
			{
				return FALSE;
			}
		}

		if ($params['base64'])
		{
			$data = base64_decode($data);
		}

		isset($params['key']) OR $params['key'] = $this->hkdf($this->_key, 'sha512', NULL, self::strlen($this->_key), 'encryption');

		return $this->{'_'.$this->_driver.'_decrypt'}($data, $params);
	}

	// --------------------------------------------------------------------

	/**
	 * Decrypt via OpenSSL
	 *
	 * @param	string	$data	Encrypted data
	 * @param	array	$params	Input parameters
	 * @return	string
	 */
	protected function _openssl_decrypt($data, $params)
	{
		if ($iv_size = openssl_cipher_iv_length($params['handle']))
		{
			$iv = self::substr($data, 0, $iv_size);
			$data = self::substr($data, $iv_size);
		}
		else
		{
			$iv = '';
		}

		if (empty($params['handle']))
		{
			return FALSE;
		}

		// GCM mode requires the authentication tag
		if (substr($params['handle'], -4) === '-gcm')
		{
			$tag = self::substr($data, 0, 16);
			$data = self::substr($data, 16);

			return openssl_decrypt(
				$data,
				$params['handle'],
				$params['key'],
				OPENSSL_RAW_DATA,
				$iv,
				$tag
			);
		}

		return openssl_decrypt(
			$data,
			$params['handle'],
			$params['key'],
			OPENSSL_RAW_DATA,
			$iv
		);
	}

	// --------------------------------------------------------------------

	/**
	 * Get params
	 *
	 * @param	array	$params	Input parameters
	 * @return	array
	 */
	protected function _get_params($params)
	{
		if (empty($params))
		{
			return isset($this->_cipher, $this->_mode, $this->_key, $this->_handle)
				? array(
					'handle' => $this->_handle,
					'cipher' => $this->_cipher,
					'mode' => $this->_mode,
					'key' => NULL,
					'base64' => TRUE,
					'hmac_digest' => 'sha512',
					'hmac_key' => NULL
				)
				: FALSE;
		}
		elseif ( ! isset($params['cipher'], $params['mode'], $params['key']))
		{
			return FALSE;
		}

		if (isset($params['mode']))
		{
			$params['mode'] = strtolower($params['mode']);
			if ( ! isset($this->_modes[$this->_driver][$params['mode']]))
			{
				return FALSE;
			}

			$params['mode'] = $this->_modes[$this->_driver][$params['mode']];
		}

		if (isset($params['hmac']) && $params['hmac'] === FALSE)
		{
			$params['hmac_digest'] = $params['hmac_key'] = NULL;
		}
		else
		{
			if ( ! isset($params['hmac_key']))
			{
				return FALSE;
			}
			elseif (isset($params['hmac_digest']))
			{
				$params['hmac_digest'] = strtolower($params['hmac_digest']);
				if ( ! isset($this->_digests[$params['hmac_digest']]))
				{
					return FALSE;
				}
			}
			else
			{
				$params['hmac_digest'] = 'sha512';
			}
		}

		$params = array(
			'handle' => NULL,
			'cipher' => $params['cipher'],
			'mode' => $params['mode'],
			'key' => $params['key'],
			'base64' => isset($params['raw_data']) ? ! $params['raw_data'] : FALSE,
			'hmac_digest' => $params['hmac_digest'],
			'hmac_key' => $params['hmac_key']
		);

		$this->_cipher_alias($params['cipher']);
		$params['handle'] = ($params['cipher'] !== $this->_cipher OR $params['mode'] !== $this->_mode)
			? $this->{'_'.$this->_driver.'_get_handle'}($params['cipher'], $params['mode'])
			: $this->_handle;

		return $params;
	}

	// --------------------------------------------------------------------

	/**
	 * Get OpenSSL handle
	 *
	 * @param	string	$cipher	Cipher name
	 * @param	string	$mode	Encryption mode
	 * @return	string
	 */
	protected function _openssl_get_handle($cipher, $mode)
	{
		// OpenSSL methods aren't suffixed with '-stream' for this mode
		return ($mode === 'stream')
			? $cipher
			: $cipher.'-'.$mode;
	}

	// --------------------------------------------------------------------

	/**
	 * Cipher alias
	 *
	 * Tries to translate legacy MCrypt cipher names to OpenSSL's "dialects".
	 *
	 * @param	string	$cipher	Cipher name
	 * @return	void
	 */
	protected function _cipher_alias(&$cipher)
	{
		static $dictionary;

		if (empty($dictionary))
		{
			$dictionary = array(
				'openssl' => array(
					'rijndael-128' => 'aes-128',
					'tripledes' => 'des-ede3',
					'blowfish' => 'bf',
					'cast-128' => 'cast5',
					'arcfour' => 'rc4-40',
					'rc4' => 'rc4-40'
				)
			);

			// Notes:
			//
			// - Rijndael-128 (MCrypt) and AES-128 (OpenSSL) are compatible.
			// - Blowfish, CAST-128 and RC4 names differ between the two.
			//   AES-192 and AES-256. The only difference between them is
			//   the key size. Rijndael-192, Rijndael-256 on the other hand
			//   also have different block sizes and are NOT AES-compatible.
			//
			// - Blowfish is said to be supporting key sizes between
			//   4 and 56 bytes, but it appears that between MCrypt and
			//   OpenSSL, only those of 16 and more bytes are compatible.
			//   Also, don't know what MCrypt's 'blowfish-compat' is.
			//
			// - CAST-128/CAST5 produces a longer cipher when encrypted via
			//   OpenSSL, but (strangely enough) can be decrypted by either
			//   extension anyway.
			//   Also, it appears that OpenSSL uses 16 rounds regardless of
			//   the key size, while RFC2144 says that for key sizes lower
			//   than 11 bytes, only 12 rounds should be used. This makes
			//   it portable only with keys of between 11 and 16 bytes.
			//
			// - RC4 (ARCFour) has a strange implementation under OpenSSL.
			//   Its 'rc4-40' cipher method seems to work flawlessly, yet
			//   there's another one, 'rc4' that only works with a 16-byte key.
			//
		}

		if (isset($dictionary[$this->_driver][$cipher]))
		{
			$cipher = $dictionary[$this->_driver][$cipher];
		}
	}

	// --------------------------------------------------------------------

	/**
	 * HKDF
	 *
	 * @link	https://tools.ietf.org/rfc/rfc5869.txt
	 * @param	$key	Input key
	 * @param	$digest	A SHA-2 hashing algorithm
	 * @param	$salt	Optional salt
	 * @param	$length	Output length (defaults to the selected digest size)
	 * @param	$info	Optional context/application-specific info
	 * @return	string	A pseudo-random key
	 */
	public function hkdf($key, $digest = 'sha512', $salt = NULL, $length = NULL, $info = '')
	{
		if ( ! isset($this->_digests[$digest]))
		{
			return FALSE;
		}

		if (empty($length) OR ! is_int($length))
		{
			$length = $this->_digests[$digest];
		}
		elseif ($length > (255 * $this->_digests[$digest]))
		{
			return FALSE;
		}

		self::strlen($salt) OR $salt = str_repeat("\0", $this->_digests[$digest]);

		$prk = hash_hmac($digest, $key, $salt, TRUE);
		$key = '';
		for ($key_block = '', $block_index = 1; self::strlen($key) < $length; $block_index++)
		{
			$key_block = hash_hmac($digest, $key_block.$info.chr($block_index), $prk, TRUE);
			$key .= $key_block;
		}

		return self::substr($key, 0, $length);
	}

	// --------------------------------------------------------------------

	/**
	 * __get() magic
	 *
	 * @param	string	$key	Property name
	 * @return	mixed
	 */
	public function __get($key)
	{
		// Because aliases
		if ($key === 'mode')
		{
			return array_search($this->_mode, $this->_modes[$this->_driver], TRUE);
		}
		elseif (in_array($key, array('cipher', 'driver', 'drivers', 'digests'), TRUE))
		{
			return $this->{'_'.$key};
		}

		return NULL;
	}

	// --------------------------------------------------------------------

	/**
	 * Byte-safe strlen()
	 *
	 * @param	string	$str
	 * @return	int
	 */
	protected static function strlen($str)
	{
		return strlen((string) $str);
	}

	// --------------------------------------------------------------------

	/**
	 * Byte-safe substr()
	 *
	 * @param	string	$str
	 * @param	int	$start
	 * @param	int	$length
	 * @return	string
	 */
	protected static function substr($str, $start, $length = NULL)
	{
		return isset($length)
			? substr($str, $start, $length)
			: substr($str, $start);
	}
}
