<?php
declare(strict_types=1);

namespace Customigniter\Console\Commands;

use Customigniter\Database\DatabaseConnection;

/**
 * Migrate Command
 *
 * Applies all pending migrations (optionally up to --to=<version>).
 *
 * @package	Customigniter3
 */
class MigrateCommand extends MigrationCommand
{
	public function getName(): string
	{
		return 'migrate';
	}

	public function getDescription(): string
	{
		return 'Apply pending database migrations';
	}

	public function getUsage(): string
	{
		return 'migrate [--group=<group>] [--to=<version>]';
	}

	public function execute(array $args): int
	{
		$parsed = $this->parseOptions($args);

		return $this->withDatabase($parsed, function (DatabaseConnection $connection) use ($parsed): int {
			$target = (int) $this->getOption($parsed, 'to', '0');
			$result = $this->runner($connection)->migrate($target);

			if ($result['migrated'] === 0)
			{
				$this->info('Nothing to migrate. Current version: '.$result['version']);
			}
			else
			{
				$this->success(
					"Migrated {$result['migrated']} migration(s). Current version: {$result['version']}"
				);
			}

			return 0;
		});
	}
}
