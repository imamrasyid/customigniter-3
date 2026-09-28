<?php
declare(strict_types=1);

namespace Customigniter\Cache;

/**
 * File-backed cache driver
 *
 * One serialised file per key inside the cache directory. Expired entries
 * are treated as misses and removed on read.
 *
 * @package	Customigniter3
 */
class FileCacheDriver implements CacheDriverInterface
{
	/**
	 * Directory holding the cache files
	 */
	private string $dir;

	// --------------------------------------------------------------------

	/**
	 * @param	string|null	$dir	Defaults to CACHE_PATH
	 */
	public function __construct(?string $dir = null)
	{
		if ($dir === null)
		{
			if ( ! defined('CACHE_PATH'))
			{
				throw new \RuntimeException('CACHE_PATH is not defined; pass a cache directory explicitly');
			}

			$dir = CACHE_PATH;
		}

		$this->dir = $dir;
	}

	// --------------------------------------------------------------------

	/**
	 * @param	string	$key
	 * @return	mixed
	 */
	public function get(string $key): mixed
	{
		$entry = $this->read($key);

		if ($entry === null)
		{
			return null;
		}

		return $entry['value'];
	}

	// --------------------------------------------------------------------

	/**
	 * @param	string	$key
	 * @param	mixed	$value
	 * @param	int		$ttl
	 * @return	bool
	 */
	public function set(string $key, mixed $value, int $ttl = 0): bool
	{
		$this->ensureDirectory();

		$entry = serialize([
			'key'     => $key,
			'value'   => $value,
			'expires' => $ttl > 0 ? time() + $ttl : 0,
		]);

		$file = $this->path($key);
		$tmp = $file.'.'.bin2hex(random_bytes(4)).'.tmp';

		if (file_put_contents($tmp, $entry, LOCK_EX) === false)
		{
			return false;
		}

		return rename($tmp, $file);
	}

	// --------------------------------------------------------------------

	/**
	 * @param	string	$key
	 * @return	bool
	 */
	public function delete(string $key): bool
	{
		$file = $this->path($key);

		if ( ! is_file($file))
		{
			return false;
		}

		return unlink($file);
	}

	// --------------------------------------------------------------------

	/**
	 * @param	string	$key
	 * @return	bool
	 */
	public function has(string $key): bool
	{
		return $this->read($key) !== null;
	}

	// --------------------------------------------------------------------

	/**
	 * @return	bool
	 */
	public function clean(): bool
	{
		$ok = true;

		foreach ($this->files() as $file)
		{
			$ok = unlink($file) && $ok;
		}

		return $ok;
	}

	// --------------------------------------------------------------------

	/**
	 * Read a live entry for the key; null on miss or expiry
	 *
	 * @param	string	$key
	 * @return	array{key: string, value: mixed, expires: int}|null
	 */
	private function read(string $key): ?array
	{
		$file = $this->path($key);

		if ( ! is_file($file))
		{
			return null;
		}

		$raw = file_get_contents($file);

		if ($raw === false)
		{
			return null;
		}

		$entry = @unserialize($raw);

		if ( ! is_array($entry) || ! isset($entry['key'], $entry['expires']) || ! array_key_exists('value', $entry))
		{
			unlink($file);

			return null;
		}

		if ( ! is_string($entry['key']))
		{
			unlink($file);

			return null;
		}

		if (is_int($entry['expires']) && $entry['expires'] > 0 && $entry['expires'] <= time())
		{
			unlink($file);

			return null;
		}

		return [
			'key'     => $entry['key'],
			'value'   => $entry['value'],
			'expires' => is_int($entry['expires']) ? $entry['expires'] : 0,
		];
	}

	// --------------------------------------------------------------------

	/**
	 * Absolute path of the file backing a key
	 *
	 * @param	string	$key
	 * @return	string
	 */
	private function path(string $key): string
	{
		return $this->dir.DIRECTORY_SEPARATOR.sha1($key).'.cache';
	}

	// --------------------------------------------------------------------

	/**
	 * Cache files currently on disk
	 *
	 * @return	array<int, string>
	 */
	private function files(): array
	{
		if ( ! is_dir($this->dir))
		{
			return [];
		}

		$files = glob($this->dir.DIRECTORY_SEPARATOR.'*.cache');

		return $files === false ? [] : $files;
	}

	// --------------------------------------------------------------------

	/**
	 * @return	void
	 */
	private function ensureDirectory(): void
	{
		if (is_dir($this->dir))
		{
			return;
		}

		if ( ! mkdir($this->dir, 0775, true) && ! is_dir($this->dir))
		{
			throw new \RuntimeException('Unable to create cache directory: '.$this->dir);
		}
	}
}
