<?php
declare(strict_types=1);

namespace Customigniter\Console\Commands;

use Customigniter\Database\DatabaseConnection;

/**
 * Migrate Status Command
 *
 * Lists every discovered migration with its applied/pending state.
 *
 * @package	Customigniter3
 */
class MigrateStatusCommand extends MigrationCommand
{
	public function getName(): string
	{
		return 'migrate:status';
	}

	public function getDescription(): string
	{
		return 'Show the status of each migration';
	}

	public function getUsage(): string
	{
		return 'migrate:status [--group=<group>]';
	}

	public function execute(array $args): int
	{
		$parsed = $this->parseOptions($args);

		return $this->withDatabase($parsed, function (DatabaseConnection $connection): int {
			$status = $this->runner($connection)->getStatus();

			if ($status === [])
			{
				$this->info('No migration files found.');
				return 0;
			}

			$this->header('Migration Status');
			fwrite(STDOUT, sprintf("%-50s %-10s %s\n", 'Migration', 'Status', 'Batch'));

			foreach ($status as $row)
			{
				$batch = $row['batch'] === null ? '-' : (string) $row['batch'];
				fwrite(STDOUT, sprintf("%-50s %-10s %s\n", $row['file'], $row['status'], $batch));
			}

			return 0;
		});
	}
}
