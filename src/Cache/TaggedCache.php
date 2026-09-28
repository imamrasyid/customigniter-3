<?php
declare(strict_types=1);

namespace Customigniter\Cache;

/**
 * Tagged view over a cache driver
 *
 * Every key written through a tag set is indexed under those tags, so
 * flush() removes exactly the keys registered for a tag.
 *
 * @package	Customigniter3
 */
class TaggedCache
{
	/**
	 * Constructor
	 *
	 * @param	CacheDriverInterface	$driver	Backing store
	 * @param	array<int, string>		$tags	Tags that index written keys
	 */
	public function __construct(
		private CacheDriverInterface $driver,
		private array $tags
	) {
	}

	// --------------------------------------------------------------------

	/**
	 * Store a value and index it under the active tags
	 *
	 * @param	string	$key
	 * @param	mixed	$value
	 * @param	int		$ttl
	 * @return	bool
	 */
	public function set(string $key, mixed $value, int $ttl = 0): bool
	{
		$ok = $this->driver->set($key, $value, $ttl);

		if ( ! $ok)
		{
			return false;
		}

		foreach ($this->tags as $tag)
		{
			$index = $this->tagIndex($tag);

			if (in_array($key, $index, true))
			{
				continue;
			}

			$index[] = $key;
			$this->driver->set($this->indexKey($tag), $index);
		}

		return true;
	}

	// --------------------------------------------------------------------

	/**
	 * Fetch a value; tags do not affect reads
	 *
	 * @param	string	$key
	 * @return	mixed
	 */
	public function get(string $key): mixed
	{
		return $this->driver->get($key);
	}

	// --------------------------------------------------------------------

	/**
	 * Remove a value and forget it from the tag indexes
	 *
	 * @param	string	$key
	 * @return	bool
	 */
	public function delete(string $key): bool
	{
		foreach ($this->tags as $tag)
		{
			$index = $this->tagIndex($tag);

			if (in_array($key, $index, true))
			{
				$index = array_values(array_filter($index, static fn (string $entry): bool => $entry !== $key));
				$this->driver->set($this->indexKey($tag), $index);
			}
		}

		return $this->driver->delete($key);
	}

	// --------------------------------------------------------------------

	/**
	 * Remove every key indexed under the active tags
	 *
	 * @return	int	Number of keys removed
	 */
	public function flush(): int
	{
		$removed = 0;

		foreach ($this->tags as $tag)
		{
			foreach ($this->tagIndex($tag) as $key)
			{
				if ($this->driver->delete($key))
				{
					$removed++;
				}
			}

			$this->driver->delete($this->indexKey($tag));
		}

		return $removed;
	}

	// --------------------------------------------------------------------

	/**
	 * Keys currently indexed under the active tags
	 *
	 * @return	array<int, string>
	 */
	public function keys(): array
	{
		$keys = [];

		foreach ($this->tags as $tag)
		{
			foreach ($this->tagIndex($tag) as $key)
			{
				$keys[] = $key;
			}
		}

		return array_values(array_unique($keys));
	}

	// --------------------------------------------------------------------

	/**
	 * Stored key index for a tag
	 *
	 * @param	string	$tag
	 * @return	array<int, string>
	 */
	private function tagIndex(string $tag): array
	{
		$index = $this->driver->get($this->indexKey($tag));

		if ( ! is_array($index))
		{
			return [];
		}

		$keys = [];

		foreach ($index as $entry)
		{
			if (is_string($entry))
			{
				$keys[] = $entry;
			}
		}

		return $keys;
	}

	// --------------------------------------------------------------------

	/**
	 * Cache key holding the index for a tag
	 *
	 * @param	string	$tag
	 * @return	string
	 */
	private function indexKey(string $tag): string
	{
		return '__tag_index__'.$tag;
	}
}
