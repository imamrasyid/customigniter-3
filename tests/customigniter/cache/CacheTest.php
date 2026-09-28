<?php
declare(strict_types=1);

use Customigniter\Cache\Cache;
use Customigniter\Cache\FileCacheDriver;

class CacheTest extends CI_TestCase
{
	private string $dir;
	private Cache $cache;

	// --------------------------------------------------------------------

	public function set_up(): void
	{
		$this->dir = sys_get_temp_dir().DIRECTORY_SEPARATOR.'ci3_cache_'.bin2hex(random_bytes(4));
		$this->cache = new Cache(new FileCacheDriver($this->dir));
	}

	// --------------------------------------------------------------------

	public function tearDown(): void
	{
		foreach (glob($this->dir.DIRECTORY_SEPARATOR.'*') ?: [] as $entry)
		{
			unlink($entry);
		}

		if (is_dir($this->dir))
		{
			rmdir($this->dir);
		}
	}

	// --------------------------------------------------------------------

	public function test_set_get_roundtrip(): void
	{
		$this->assertTrue($this->cache->set('key', ['a' => 1, 'b' => 'two']));
		$this->assertSame(['a' => 1, 'b' => 'two'], $this->cache->get('key'));
		$this->assertTrue($this->cache->has('key'));
	}

	// --------------------------------------------------------------------

	public function test_miss_returns_null(): void
	{
		$this->assertNull($this->cache->get('absent'));
		$this->assertFalse($this->cache->has('absent'));
	}

	// --------------------------------------------------------------------

	public function test_delete(): void
	{
		$this->cache->set('key', 'value');

		$this->assertTrue($this->cache->delete('key'));
		$this->assertFalse($this->cache->has('key'));
		$this->assertFalse($this->cache->delete('key'));
	}

	// --------------------------------------------------------------------

	public function test_expired_entry_is_a_miss(): void
	{
		$this->cache->set('key', 'value', 1);

		$this->assertTrue($this->cache->has('key'));

		sleep(1);

		$this->assertFalse($this->cache->has('key'));
		$this->assertNull($this->cache->get('key'));
	}

	// --------------------------------------------------------------------

	public function test_ttl_zero_keeps_value(): void
	{
		$this->cache->set('key', 'value', 0);

		$this->assertTrue($this->cache->has('key'));
	}

	// --------------------------------------------------------------------

	public function test_clean_removes_everything(): void
	{
		$this->cache->set('a', 1);
		$this->cache->set('b', 2);

		$this->assertTrue($this->cache->clean());
		$this->assertFalse($this->cache->has('a'));
		$this->assertFalse($this->cache->has('b'));
	}

	// --------------------------------------------------------------------

	public function test_corrupt_entry_is_treated_as_a_miss(): void
	{
		$dir = $this->dir;

		if ( ! is_dir($dir))
		{
			mkdir($dir, 0775, true);
		}

		file_put_contents($dir.DIRECTORY_SEPARATOR.sha1('key').'.cache', 'not-a-serialised-entry');

		$this->assertNull($this->cache->get('key'));
		$this->assertFalse($this->cache->has('key'));
	}

	// --------------------------------------------------------------------

	public function test_tags_index_written_keys(): void
	{
		$tagged = $this->cache->tags(['posts']);

		$tagged->set('p1', 'first');
		$tagged->set('p2', 'second');

		$this->assertSame(['p1', 'p2'], $tagged->keys());
		$this->assertSame('first', $tagged->get('p1'));
	}

	// --------------------------------------------------------------------

	public function test_flush_removes_tagged_keys_only(): void
	{
		$this->cache->set('untouched', 'keep');

		$tagged = $this->cache->tags(['posts']);
		$tagged->set('p1', 'first');
		$tagged->set('p2', 'second');

		$this->assertSame(2, $tagged->flush());
		$this->assertSame([], $tagged->keys());
		$this->assertFalse($this->cache->has('p1'));
		$this->assertFalse($this->cache->has('p2'));
		$this->assertSame('keep', $this->cache->get('untouched'));
	}

	// --------------------------------------------------------------------

	public function test_flushing_one_tag_leaves_other_tags(): void
	{
		$posts = $this->cache->tags(['posts']);
		$posts->set('shared', 'v');

		$users = $this->cache->tags(['users']);
		$users->set('shared', 'v');

		$posts->flush();

		$this->assertFalse($this->cache->has('shared'));
		$this->assertSame([], $posts->keys());
		$this->assertSame(['shared'], $users->keys());
	}

	// --------------------------------------------------------------------

	public function test_key_written_under_two_tags_is_flushed_by_either(): void
	{
		$tagged = $this->cache->tags(['a', 'b']);
		$tagged->set('both', 'v');

		$this->assertSame(1, $this->cache->tags(['a'])->flush());
		$this->assertFalse($this->cache->has('both'));
	}

	// --------------------------------------------------------------------

	public function test_delete_updates_tag_index(): void
	{
		$tagged = $this->cache->tags(['posts']);
		$tagged->set('p1', 'first');
		$tagged->set('p2', 'second');

		$tagged->delete('p1');

		$this->assertSame(['p2'], $tagged->keys());
		$this->assertFalse($this->cache->has('p1'));
	}
}
