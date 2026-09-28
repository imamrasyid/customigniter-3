<?php
declare(strict_types=1);

namespace Customigniter\Queue;

/**
 * Job queue contract
 *
 * @package	Customigniter3
 */
interface QueueInterface
{
	/**
	 * Append a job to the queue
	 *
	 * @param	string					$job	Fully qualified job class name
	 * @param	array<string, mixed>	$data	Payload passed to JobInterface::handle()
	 * @param	int						$delay	Seconds to wait before the job becomes available
	 * @return	string	Generated job id
	 */
	public function push(string $job, array $data = [], int $delay = 0): string;

	/**
	 * Number of queued jobs, including delayed ones
	 */
	public function size(): int;

	/**
	 * Remove and return the next available job
	 *
	 * @return	array{id: string, job: string, data: array<string, mixed>}|null
	 */
	public function pop(): ?array;

	/**
	 * Discard every queued job
	 *
	 * @return	void
	 */
	public function clear(): void;
}
