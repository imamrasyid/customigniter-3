<?php
declare(strict_types=1);

namespace Customigniter\Security;

/**
 * Modern Password Hashing Wrapper
 *
 * Provides a clean API for password hashing and verification using
 * modern PHP algorithms (Argon2id preferred, Bcrypt fallback).
 *
 * @package	Customigniter3
 */
class PasswordHasher
{
	/**
	 * Available hashing algorithms by preference order
	 */
	private const ALGORITHMS = [
		'argon2id' => PASSWORD_ARGON2ID,
		'argon2i'  => PASSWORD_ARGON2I,
		'bcrypt'   => PASSWORD_BCRYPT,
	];

	/**
	 * Default options for each algorithm
	 */
	private const DEFAULT_OPTIONS = [
		'argon2id' => [
			'memory_cost' => 65536,  // 64 MB
			'time_cost'   => 4,
			'threads'     => 3,
		],
		'argon2i' => [
			'memory_cost' => 65536,
			'time_cost'   => 4,
			'threads'     => 3,
		],
		'bcrypt' => [
			'cost' => 12,
		],
	];

	/**
	 * Map friendly names to password_algos() output
	 */
	private const ALGO_NAMES = [
		'argon2id' => 'argon2id',
		'argon2i'  => 'argon2i',
		'bcrypt'   => '2y',
	];

	/**
	 * Preferred algorithm
	 *
	 * @var string
	 */
	private string $preferredAlgorithm;

	/**
	 * Constructor
	 *
	 * @param	string	$preferredAlgorithm	Preferred algorithm ('auto', 'argon2id', 'argon2i', 'bcrypt')
	 */
	public function __construct(string $preferredAlgorithm = 'auto')
	{
		$this->preferredAlgorithm = $preferredAlgorithm;
	}

	/**
	 * Hash a password
	 *
	 * @param	string	$password
	 * @param	string	$algorithm
	 * @return	string	Hashed password
	 * @throws	\RuntimeException
	 */
	public function hash(string $password, string $algorithm = ''): string
	{
		$algo = $this->resolveAlgorithm($algorithm);
		$options = self::DEFAULT_OPTIONS[$algo] ?? [];

		$hash = password_hash($password, self::ALGORITHMS[$algo], $options);

		if ($hash === false) {
			throw new \RuntimeException("Failed to hash password using algorithm: {$algo}");
		}

		return $hash;
	}

	/**
	 * Verify a password against a hash
	 *
	 * @param	string	$password
	 * @param	string	$hash
	 * @return	bool
	 */
	public function verify(string $password, string $hash): bool
	{
		return password_verify($password, $hash);
	}

	/**
	 * Check if the hash needs to be rehashed (e.g., algorithm changed or cost increased)
	 *
	 * @param	string	$hash
	 * @param	string	$algorithm
	 * @return	bool
	 */
	public function needsRehash(string $hash, string $algorithm = ''): bool
	{
		$algo = $this->resolveAlgorithm($algorithm);
		$options = self::DEFAULT_OPTIONS[$algo] ?? [];

		return password_needs_rehash($hash, self::ALGORITHMS[$algo], $options);
	}

	/**
	 * Get info about a hash
	 *
	 * @param	string	$hash
	 * @return	array{algo: string, algoName: string, options: array}
	 */
	public function info(string $hash): array
	{
		return password_get_info($hash);
	}

	/**
	 * Check if an algorithm is available
	 *
	 * @param	string	$algorithm
	 * @return	bool
	 */
	public static function isAlgorithmAvailable(string $algorithm): bool
	{
		if ($algorithm === 'auto') {
			return true;
		}

		if ( ! isset(self::ALGORITHMS[$algorithm])) {
			return false;
		}

		$algoName = self::ALGO_NAMES[$algorithm] ?? $algorithm;

		return in_array($algoName, password_algos(), true);
	}

	/**
	 * Get the best available algorithm
	 *
	 * @return	string
	 */
	public function getBestAvailableAlgorithm(): string
	{
		foreach (['argon2id', 'argon2i', 'bcrypt'] as $algo) {
			if (self::isAlgorithmAvailable($algo)) {
				return $algo;
			}
		}

		return 'bcrypt';
	}

	/**
	 * Resolve algorithm name to use
	 *
	 * @param	string	$algorithm
	 * @return	string
	 */
	private function resolveAlgorithm(string $algorithm): string
	{
		if ($algorithm === '' || $algorithm === 'auto') {
			if ($this->preferredAlgorithm === 'auto') {
				return $this->getBestAvailableAlgorithm();
			}

			$algorithm = $this->preferredAlgorithm;
		}

		if ( ! self::isAlgorithmAvailable($algorithm)) {
			throw new \RuntimeException("Password hashing algorithm not available: {$algorithm}");
		}

		return $algorithm;
	}
}
