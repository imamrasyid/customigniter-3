<?php
declare(strict_types=1);

namespace Customigniter\Console\Commands;

use Customigniter\Database\DatabaseConnection;

/**
 * Migrate Rollback Command
 *
 * Reverts the most recent migration batch(es).
 *
 * @package	Customigniter3
 */
class MigrateRollbackCommand extends MigrationCommand
{
	public function getName(): string
	{
		return 'migrate:rollback';
	}

	public function getDescription(): string
	{
		return 'Rollback the last migration batch';
	}

	public function getUsage(): string
	{
		return 'migrate:rollback [--group=<group>] [--steps=<n>]';
	}

	public function execute(array $args): int
	{
		$parsed = $this->parseOptions($args);

		return $this->withDatabase($parsed, function (DatabaseConnection $connection) use ($parsed): int {
			$steps = max(1, (int) $this->getOption($parsed, 'steps', '1'));
			$result = $this->runner($connection)->rollback($steps);

			if ($result['rolled_back'] === 0)
			{
				$this->info('Nothing to roll back.');
			}
			else
			{
				$this->success(
					"Rolled back {$result['rolled_back']} migration(s). Current version: {$result['version']}"
				);
			}

			return 0;
		});
	}
}
