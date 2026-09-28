<?php
declare(strict_types=1);

namespace Customigniter\Queue;

/**
 * File-backed FIFO queue
 *
 * One JSON file per job inside the queue directory. Filenames start with
 * the availability timestamp so popping is a simple ordered scan.
 *
 * @package	Customigniter3
 */
class FileQueue implements QueueInterface
{
	/**
	 * Directory holding the job files
	 */
	private string $dir;

	// --------------------------------------------------------------------

	/**
	 * @param	string|null	$dir	Defaults to CACHE_PATH/queue
	 */
	public function __construct(?string $dir = null)
	{
		if ($dir === null)
		{
			if ( ! defined('CACHE_PATH'))
			{
				throw new \RuntimeException('CACHE_PATH is not defined; pass a queue directory explicitly');
			}

			$dir = rtrim(CACHE_PATH, '/\\').DIRECTORY_SEPARATOR.'queue';
		}

		$this->dir = $dir;
	}

	// --------------------------------------------------------------------

	/**
	 * @param	string					$job
	 * @param	array<string, mixed>	$data
	 * @param	int						$delay
	 * @return	string
	 */
	public function push(string $job, array $data = [], int $delay = 0): string
	{
		$id = bin2hex(random_bytes(8));
		$availableAt = time() + max(0, $delay);

		$payload = json_encode(['id' => $id, 'job' => $job, 'data' => $data], JSON_UNESCAPED_UNICODE);

		if ($payload === false)
		{
			throw new \RuntimeException('Queue payload is not JSON encodable: '.json_last_error_msg());
		}

		$this->ensureDirectory();

		$file = $this->dir.DIRECTORY_SEPARATOR.sprintf('%011d_%s.json', $availableAt, $id);

		if (file_put_contents($file, $payload, LOCK_EX) === false)
		{
			throw new \RuntimeException('Unable to write queue file: '.$file);
		}

		return $id;
	}

	// --------------------------------------------------------------------

	/**
	 * @return	int
	 */
	public function size(): int
	{
		return count($this->jobFiles());
	}

	// --------------------------------------------------------------------

	/**
	 * @return	array{id: string, job: string, data: array<string, mixed>}|null
	 */
	public function pop(): ?array
	{
		$now = time();

		foreach ($this->jobFiles() as $file)
		{
			$name = basename($file);

			if ((int) substr($name, 0, 11) > $now)
			{
				continue;
			}

			$raw = file_get_contents($file);

			if ($raw === false)
			{
				continue;
			}

			unlink($file);

			$decoded = json_decode($raw, true);

			if ( ! is_array($decoded) || ! isset($decoded['id'], $decoded['job']) || ! is_string($decoded['id']) || ! is_string($decoded['job']))
			{
				continue;
			}

			$data = [];

			if (isset($decoded['data']) && is_array($decoded['data']))
			{
				foreach ($decoded['data'] as $key => $value)
				{
					if (is_string($key))
					{
						$data[$key] = $value;
					}
				}
			}

			return [
				'id'   => $decoded['id'],
				'job'  => $decoded['job'],
				'data' => $data,
			];
		}

		return null;
	}

	// --------------------------------------------------------------------

	/**
	 * @return	void
	 */
	public function clear(): void
	{
		foreach ($this->jobFiles() as $file)
		{
			unlink($file);
		}
	}

	// --------------------------------------------------------------------

	/**
	 * Absolute paths of queued job files, oldest first
	 *
	 * @return	array<int, string>
	 */
	private function jobFiles(): array
	{
		if ( ! is_dir($this->dir))
		{
			return [];
		}

		$files = glob($this->dir.DIRECTORY_SEPARATOR.'*.json');

		if ($files === false)
		{
			return [];
		}

		sort($files, SORT_STRING);

		return $files;
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
			throw new \RuntimeException('Unable to create queue directory: '.$this->dir);
		}
	}
}
