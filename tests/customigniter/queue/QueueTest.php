<?php
declare(strict_types=1);

use Customigniter\Queue\ArrayQueue;
use Customigniter\Queue\FileQueue;
use Customigniter\Queue\JobInterface;
use Customigniter\Queue\Worker;

class QueueTestJob implements JobInterface
{
	/** @var array<int, array<string, mixed>> */
	public static array $ran = [];

	// --------------------------------------------------------------------

	public function handle(array $data): void
	{
		self::$ran[] = $data;
	}
}

class QueueTestThrowingJob implements JobInterface
{
	public function handle(array $data): void
	{
		throw new RuntimeException('boom');
	}
}

class QueueTestBadJob
{
}

class QueueTest extends CI_TestCase
{
	private string $dir;

	// --------------------------------------------------------------------

	public function set_up(): void
	{
		$this->dir = sys_get_temp_dir().DIRECTORY_SEPARATOR.'ci3_queue_'.bin2hex(random_bytes(4));
		QueueTestJob::$ran = [];
	}

	// --------------------------------------------------------------------

	public function tearDown(): void
	{
		$this->removeDir($this->dir);
	}

	// --------------------------------------------------------------------

	public function test_array_queue_is_fifo(): void
	{
		$queue = new ArrayQueue();

		$first = $queue->push(QueueTestJob::class, ['n' => 1]);
		$second = $queue->push(QueueTestJob::class, ['n' => 2]);

		$this->assertSame(2, $queue->size());

		$a = $queue->pop();
		$b = $queue->pop();

		$this->assertIsArray($a);
		$this->assertIsArray($b);
		$this->assertSame($first, $a['id']);
		$this->assertSame(QueueTestJob::class, $a['job']);
		$this->assertSame(['n' => 1], $a['data']);
		$this->assertSame($second, $b['id']);
		$this->assertSame(0, $queue->size());
		$this->assertNull($queue->pop());
	}

	// --------------------------------------------------------------------

	public function test_array_queue_honours_delay(): void
	{
		$queue = new ArrayQueue();
		$queue->push(QueueTestJob::class, [], 3600);

		$this->assertNull($queue->pop());
		$this->assertSame(1, $queue->size());

		$queue->clear();
		$this->assertSame(0, $queue->size());
	}

	// --------------------------------------------------------------------

	public function test_file_queue_roundtrip(): void
	{
		$queue = new FileQueue($this->dir);
		$id = $queue->push('SomeJob', ['payload' => 'x']);

		$this->assertSame(1, $queue->size());

		$job = $queue->pop();

		$this->assertIsArray($job);
		$this->assertSame($id, $job['id']);
		$this->assertSame('SomeJob', $job['job']);
		$this->assertSame(['payload' => 'x'], $job['data']);
		$this->assertSame(0, $queue->size());
		$this->assertNull($queue->pop());
	}

	// --------------------------------------------------------------------

	public function test_file_queue_honours_delay(): void
	{
		$queue = new FileQueue($this->dir);
		$queue->push('SomeJob', [], 3600);

		$this->assertNull($queue->pop());
		$this->assertSame(1, $queue->size());
	}

	// --------------------------------------------------------------------

	public function test_file_queue_clear(): void
	{
		$queue = new FileQueue($this->dir);
		$queue->push('A');
		$queue->push('B');

		$queue->clear();

		$this->assertSame(0, $queue->size());
	}

	// --------------------------------------------------------------------

	public function test_worker_runs_job_with_payload(): void
	{
		$queue = new ArrayQueue();
		$queue->push(QueueTestJob::class, ['n' => 7]);

		$processed = (new Worker($queue))->run();

		$this->assertSame(1, $processed);
		$this->assertSame([['n' => 7]], QueueTestJob::$ran);
	}

	// --------------------------------------------------------------------

	public function test_worker_respects_limit(): void
	{
		$queue = new ArrayQueue();
		$queue->push(QueueTestJob::class);
		$queue->push(QueueTestJob::class);
		$queue->push(QueueTestJob::class);

		$processed = (new Worker($queue))->run(2);

		$this->assertSame(2, $processed);
		$this->assertSame(1, $queue->size());
		$this->assertCount(2, QueueTestJob::$ran);
	}

	// --------------------------------------------------------------------

	public function test_worker_skips_unknown_class(): void
	{
		$queue = new ArrayQueue();
		$queue->push('NopeNotAJobClass');

		$this->assertSame(1, (new Worker($queue))->run());
		$this->assertSame(0, $queue->size());
	}

	// --------------------------------------------------------------------

	public function test_worker_skips_class_without_interface(): void
	{
		$queue = new ArrayQueue();
		$queue->push(QueueTestBadJob::class);

		$this->assertSame(1, (new Worker($queue))->run());
		$this->assertSame(0, $queue->size());
	}

	// --------------------------------------------------------------------

	public function test_worker_survives_throwing_job(): void
	{
		$queue = new ArrayQueue();
		$queue->push(QueueTestThrowingJob::class);

		$this->assertSame(1, (new Worker($queue))->run());
		$this->assertSame(0, $queue->size());
	}

	// --------------------------------------------------------------------

	public function test_run_once_returns_false_when_idle(): void
	{
		$queue = new ArrayQueue();

		$this->assertFalse((new Worker($queue))->runOnce());

		$queue->push(QueueTestJob::class);

		$this->assertTrue((new Worker($queue))->runOnce());
		$this->assertCount(1, QueueTestJob::$ran);
	}

	// --------------------------------------------------------------------

	/**
	 * Remove a directory tree
	 *
	 * @param	string	$dir
	 * @return	void
	 */
	private function removeDir(string $dir): void
	{
		if ( ! is_dir($dir))
		{
			return;
		}

		foreach (glob($dir.DIRECTORY_SEPARATOR.'*') ?: [] as $entry)
		{
			if (is_dir($entry))
			{
				$this->removeDir($entry);
			}
			else
			{
				unlink($entry);
			}
		}

		rmdir($dir);
	}
}
