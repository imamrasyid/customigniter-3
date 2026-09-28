<?php
declare(strict_types=1);

namespace Customigniter\Queue;

/**
 * Queue worker
 *
 * Pops jobs and dispatches them to JobInterface implementations. Job
 * classes that are missing, malformed or throwing are logged and skipped
 * so a single bad job cannot stall the worker.
 *
 * @package	Customigniter3
 */
class Worker
{
	// --------------------------------------------------------------------

	/**
	 * Constructor
	 *
	 * @param	QueueInterface	$queue	Queue to drain
	 */
	public function __construct(private QueueInterface $queue)
	{
	}

	// --------------------------------------------------------------------

	/**
	 * Process jobs until the queue is empty or the limit is reached
	 *
	 * @param	int|null	$max	Maximum jobs to process; null = no limit
	 * @return	int	Number of jobs processed
	 */
	public function run(?int $max = null): int
	{
		$processed = 0;

		while ($max === null || $processed < $max)
		{
			$job = $this->queue->pop();

			if ($job === null)
			{
				break;
			}

			$this->execute($job);
			$processed++;
		}

		return $processed;
	}

	// --------------------------------------------------------------------

	/**
	 * Process a single job if one is available
	 *
	 * @return	bool	True when a job was popped
	 */
	public function runOnce(): bool
	{
		$job = $this->queue->pop();

		if ($job === null)
		{
			return false;
		}

		$this->execute($job);

		return true;
	}

	// --------------------------------------------------------------------

	/**
	 * Run one job payload through the JobInterface contract
	 *
	 * @param	array{id: string, job: string, data: array<string, mixed>}	$job
	 * @return	bool	True when the job ran successfully
	 */
	private function execute(array $job): bool
	{
		$class = $job['job'];

		if ( ! class_exists($class))
		{
			$this->log('error', 'Queue job class not found: '.$class);

			return false;
		}

		$instance = new $class();

		if ( ! $instance instanceof JobInterface)
		{
			$this->log('error', 'Queue job does not implement JobInterface: '.$class);

			return false;
		}

		try
		{
			$instance->handle($job['data']);
		}
		catch (\Throwable $e)
		{
			$this->log('error', 'Queue job failed: '.$class.' - '.$e->getMessage());

			return false;
		}

		return true;
	}

	// --------------------------------------------------------------------

	/**
	 * Route a message through CI logging when available
	 *
	 * @param	string	$level	Log level
	 * @param	string	$message	Message to record
	 * @return	void
	 */
	private function log(string $level, string $message): void
	{
		if (function_exists('log_message'))
		{
			log_message($level, $message);
		}
	}
}
