<?php
declare(strict_types=1);

namespace Customigniter\Cache;

/**
 * Cache entry point with tag support
 *
 * @package	Customigniter3
 */
class Cache
{
	/**
	 * Backing store
	 */
	private CacheDriverInterface $driver;

	// --------------------------------------------------------------------

	/**
	 * Constructor
	 *
	 * @param	CacheDriverInterface|null	$driver	Defaults to the file driver
	 */
	public function __construct(?CacheDriverInterface $driver = null)
	{
		$this->driver = $driver ?? new FileCacheDriver();
	}

	// --------------------------------------------------------------------

	/**
	 * @param	string	$key
	 * @return	mixed
	 */
	public function get(string $key): mixed
	{
		return $this->driver->get($key);
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
		return $this->driver->set($key, $value, $ttl);
	}

	// --------------------------------------------------------------------

	/**
	 * @param	string	$key
	 * @return	bool
	 */
	public function delete(string $key): bool
	{
		return $this->driver->delete($key);
	}

	// --------------------------------------------------------------------

	/**
	 * @param	string	$key
	 * @return	bool
	 */
	public function has(string $key): bool
	{
		return $this->driver->has($key);
	}

	// --------------------------------------------------------------------

	/**
	 * @return	bool
	 */
	public function clean(): bool
	{
		return $this->driver->clean();
	}

	// --------------------------------------------------------------------

	/**
	 * Scoped operations that index keys under tags
	 *
	 * @param	array<int, string>	$tags
	 * @return	TaggedCache
	 */
	public function tags(array $tags): TaggedCache
	{
		return new TaggedCache($this->driver, array_values($tags));
	}
}
