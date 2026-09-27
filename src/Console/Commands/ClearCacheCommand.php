<?php
declare(strict_types=1);

namespace Customigniter\Console\Commands;

use Customigniter\Console\Command;

/**
 * Clear Cache Command
 *
 * Clears the application cache directory.
 *
 * @package	Customigniter3
 */
class ClearCacheCommand extends Command
{
	public function getName(): string
	{
		return 'cache:clear';
	}

	public function getDescription(): string
	{
		return 'Clear application cache files';
	}

	public function getUsage(): string
	{
		return 'cache:clear [--type=<driver>]';
	}

	public function execute(array $args): int
	{
		$parsed = $this->parseOptions($args);
		$type = $this->getOption($parsed, 'type', 'files');

		if ( ! in_array($type, ['files'], true)) {
			$this->error("Unsupported cache type '{$type}'. Supported types: files");
			return 1;
		}

		if ( ! defined('BASEPATH')) {
			$this->error('Error: This command must be run through the customigniter console entry point.');
			return 1;
		}

		$cachePath = defined('CACHE_PATH') ? CACHE_PATH : APPPATH . 'cache';

		if ( ! is_dir($cachePath)) {
			$this->warn("Cache directory does not exist: {$cachePath}");
			return 0;
		}

		$count = 0;
		$failed = 0;
		$handle = opendir($cachePath);

		if ($handle === false) {
			$this->error("Cannot open cache directory: {$cachePath}");
			return 1;
		}

		while (($file = readdir($handle)) !== false) {
			if ($file === '.' || $file === '..') {
				continue;
			}

			$filePath = $cachePath . DIRECTORY_SEPARATOR . $file;

			if (is_file($filePath)) {
				if (@unlink($filePath)) {
					$count++;
				}
				else {
					$failed++;
				}
			}
		}

		closedir($handle);

		if ($failed > 0) {
			$this->warn("Cache partially cleared: {$count} file(s) removed, {$failed} could not be deleted.");
			return 1;
		}

		$this->success("Cache cleared: {$count} file(s) removed.");

		return 0;
	}
}
