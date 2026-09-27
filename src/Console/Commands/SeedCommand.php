<?php
declare(strict_types=1);

namespace Customigniter\Console\Commands;

use Customigniter\Database\DatabaseConnection;
use Customigniter\Database\SeederRunner;

/**
 * Database Seed Command
 *
 * Executes a seeder class from application/database/seeds.
 *
 * @package	Customigniter3
 */
class SeedCommand extends DatabaseCommand
{
	public function getName(): string
	{
		return 'db:seed';
	}

	public function getDescription(): string
	{
		return 'Run a database seeder class';
	}

	public function getUsage(): string
	{
		return 'db:seed <SeederName> [--group=<group>]';
	}

	public function execute(array $args): int
	{
		$parsed = $this->parseOptions($args);
		$name = $parsed['args'][0] ?? '';

		if ($name === '')
		{
			$this->error('Usage: '.$this->getUsage());
			return 1;
		}

		return $this->withDatabase($parsed, function (DatabaseConnection $connection) use ($name): int {
			$runner = new SeederRunner($connection->db, APPPATH.'database/seeds/');
			$runner->run($name);

			$this->success("Seeded: {$name}");

			return 0;
		});
	}
}
