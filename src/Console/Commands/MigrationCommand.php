<?php
declare(strict_types=1);

namespace Customigniter\Console\Commands;

use Customigniter\Database\DatabaseConnection;
use Customigniter\Database\Migration\MigrationConfig;
use Customigniter\Database\Migration\MigrationRunner;

/**
 * Migration Command Base
 *
 * Loads application/config/migration.php and builds a runner for
 * the connected database.
 *
 * @package	Customigniter3
 */
abstract class MigrationCommand extends DatabaseCommand
{
	/**
	 * Migration config values
	 *
	 * @var array{path: string, table: string}|null
	 */
	private ?array $config = null;

	// --------------------------------------------------------------------

	/**
	 * Get migration path and tracking table from the config file
	 *
	 * @return	array{path: string, table: string}
	 */
	protected function migrationConfig(): array
	{
		if ($this->config === null)
		{
			$this->config = MigrationConfig::load(
				APPPATH.'config/migration.php',
				APPPATH.'migrations/',
				'migrations'
			);
		}

		return $this->config;
	}

	// --------------------------------------------------------------------

	/**
	 * Build a migration runner for the open connection
	 *
	 * @param	DatabaseConnection	$connection
	 * @return	MigrationRunner
	 */
	protected function runner(DatabaseConnection $connection): MigrationRunner
	{
		$config = $this->migrationConfig();

		return new MigrationRunner(
			$connection->db,
			$connection->forge,
			$config['path'],
			$config['table']
		);
	}
}
