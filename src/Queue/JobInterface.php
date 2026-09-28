<?php
declare(strict_types=1);

namespace Customigniter\Queue;

/**
 * Contract for queued jobs
 *
 * @package	Customigniter3
 */
interface JobInterface
{
	/**
	 * Execute the job
	 *
	 * @param	array<string, mixed>	$data	Payload the job was pushed with
	 * @return	void
	 */
	public function handle(array $data): void;
}
