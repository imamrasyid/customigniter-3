<?php
declare(strict_types=1);

namespace Customigniter\Console\Commands;

use Customigniter\Console\Command;

/**
 * Optimize Command
 *
 * Clears the framework's file cache and resets the OPcache, the
 * usual steps after deploying changed PHP files.
 *
 * @package	Customigniter3
 */
class OptimizeCommand extends Command
{
	public function getName(): string
	{
		return 'optimize';
	}

	public function getDescription(): string
	{
		return 'Clear file caches and reset the OPcache';
	}

	public function getUsage(): string
	{
		return 'optimize';
	}

	public function execute(array $args): int
	{
		$failed = 0;

		// File cache (config cache_path, else CACHE_PATH, default application/cache)
		$cachePath = APPPATH.'cache';
		$item = config_item('cache_path');

		if (is_string($item) AND $item !== '')
		{
			$cachePath = $item;
		}
		elseif (defined('CACHE_PATH'))
		{
			$cachePath = CACHE_PATH;
		}

		if (is_dir($cachePath))
		{
			$handle = opendir($cachePath);

			if ($handle === false)
			{
				$this->error("Cannot open cache directory: {$cachePath}");
				$failed++;
			}
			else
			{
				$count = 0;

				while (($file = readdir($handle)) !== false)
				{
					if ($file === '.' || $file === '..')
					{
						continue;
					}

					$filePath = $cachePath.DIRECTORY_SEPARATOR.$file;

					if (is_file($filePath) && ! in_array($file, ['index.html', '.htaccess'], true))
					{
						@unlink($filePath) ? $count++ : $failed++;
					}
				}

				closedir($handle);
				$this->info("File cache cleared: {$count} file(s) removed.");
			}
		}
		else
		{
			$this->info('No file cache directory found.');
		}

		// OPcache
		if (function_exists('opcache_reset'))
		{
			if (@opcache_reset())
			{
				$this->info('OPcache reset.');
			}
			else
			{
				$this->warn('OPcache could not be reset (not enabled or restricted).');
			}
		}
		else
		{
			$this->info('OPcache extension not loaded; skipped.');
		}

		if ($failed > 0)
		{
			$this->error("Optimize finished with {$failed} failure(s).");
			return 1;
		}

		$this->success('Optimize complete.');

		return 0;
	}
}
