<?php
declare(strict_types=1);

namespace Customigniter\Queue;

/**
 * In-memory FIFO queue
 *
 * Useful for tests and single-process runs.
 *
 * @package	Customigniter3
 */
class ArrayQueue implements QueueInterface
{
	/**
	 * Queued jobs
	 *
	 * @var	array<int, array{id: string, job: string, data: array<string, mixed>, available_at: int}>
	 */
	private array $items = [];

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

		$this->items[] = [
			'id'           => $id,
			'job'          => $job,
			'data'         => $data,
			'available_at' => time() + max(0, $delay),
		];

		return $id;
	}

	// --------------------------------------------------------------------

	/**
	 * @return	int
	 */
	public function size(): int
	{
		return count($this->items);
	}

	// --------------------------------------------------------------------

	/**
	 * @return	array{id: string, job: string, data: array<string, mixed>}|null
	 */
	public function pop(): ?array
	{
		$now = time();

		foreach ($this->items as $index => $item)
		{
			if ($item['available_at'] > $now)
			{
				continue;
			}

			array_splice($this->items, $index, 1);

			return [
				'id'   => $item['id'],
				'job'  => $item['job'],
				'data' => $item['data'],
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
		$this->items = [];
	}
}
