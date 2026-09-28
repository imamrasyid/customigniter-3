<?php
declare(strict_types=1);

namespace Customigniter\Console\Commands;

use Customigniter\Console\Command;
use Customigniter\Queue\FileQueue;
use Customigniter\Queue\Worker;

/**
 * Queue Work Command
 *
 * Processes queued jobs from the file queue.
 *
 * @package	Customigniter3
 */
class QueueWorkCommand extends Command
{
	public function getName(): string
	{
		return 'queue:work';
	}

	// --------------------------------------------------------------------

	public function getDescription(): string
	{
		return 'Process queued jobs';
	}

	// --------------------------------------------------------------------

	public function getUsage(): string
	{
		return 'queue:work [--once] [--limit=<count>] [--queue=<directory>]';
	}

	// --------------------------------------------------------------------

	public function execute(array $args): int
	{
		$parsed = $this->parseOptions($args);

		if ( ! defined('BASEPATH'))
		{
			$this->error('Error: This command must be run through the customigniter console entry point.');

			return 1;
		}

		$dir = $this->getOption($parsed, 'queue', '');

		if ($dir === '')
		{
			$base = defined('CACHE_PATH') ? CACHE_PATH : (defined('APPPATH') ? APPPATH.'cache' : '');
			$dir = $base === '' ? '' : rtrim($base, '/\\').DIRECTORY_SEPARATOR.'queue';
		}

		try
		{
			$queue = new FileQueue($dir === '' ? null : $dir);
		}
		catch (\RuntimeException $e)
		{
			$this->error($e->getMessage());

			return 1;
		}

		$worker = new Worker($queue);

		if ($this->hasFlag($parsed, 'once'))
		{
			$worker->runOnce();
			$this->success('Processed 1 job (or idle)');

			return 0;
		}

		$limit = $this->getOption($parsed, 'limit', '');
		$max = $limit !== '' && ctype_digit($limit) ? (int) $limit : null;

		$processed = $worker->run($max);
		$this->success("Processed {$processed} job(s)");

		return 0;
	}
}
