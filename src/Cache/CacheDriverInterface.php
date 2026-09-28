<?php
declare(strict_types=1);

namespace Customigniter\Cache;

/**
 * Storage backend contract for the cache layer
 *
 * @package	Customigniter3
 */
interface CacheDriverInterface
{
	/**
	 * Fetch a value; null when missing or expired
	 *
	 * @param	string	$key
	 * @return	mixed
	 */
	public function get(string $key): mixed;

	/**
	 * Store a value
	 *
	 * @param	string	$key
	 * @param	mixed	$value	Any serialisable value
	 * @param	int		$ttl	Seconds until expiry; 0 = forever
	 * @return	bool
	 */
	public function set(string $key, mixed $value, int $ttl = 0): bool;

	/**
	 * Remove a value
	 *
	 * @param	string	$key
	 * @return	bool	True when something was removed
	 */
	public function delete(string $key): bool;

	/**
	 * Whether a live value exists
	 *
	 * @param	string	$key
	 * @return	bool
	 */
	public function has(string $key): bool;

	/**
	 * Remove every value in the store
	 *
	 * @return	bool
	 */
	public function clean(): bool;
}
