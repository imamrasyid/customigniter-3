<?php
declare(strict_types=1);

namespace Customigniter\Console\Commands;

use Customigniter\Console\Command;
use Customigniter\Database\CliDatabase;
use Customigniter\Database\DatabaseConnection;

/**
 * Database Command Base
 *
 * Provides a guarded database connection flow for commands that
 * need the query builder and forge outside the request cycle.
 *
 * @package	Customigniter3
 */
abstract class DatabaseCommand extends Command
{
	/**
	 * Run a callback with an open database connection
	 *
	 * Connection and callback failures are reported as command errors.
	 *
	 * @param	array{flags: array<int, string>, options: array<string, string>, args: array<int, string>}	$parsed
	 * @param	callable(DatabaseConnection): int	$callback
	 * @return	int	Exit code
	 */
	protected function withDatabase(array $parsed, callable $callback): int
	{
		try {
			$connection = CliDatabase::connect($this->getOption($parsed, 'group', ''));
		}
		catch (\Throwable $e) {
			$this->error('Database connection failed: '.$e->getMessage());
			return 1;
		}

		try {
			return $callback($connection);
		}
		catch (\Throwable $e) {
			$this->error($e->getMessage());
			return 1;
		}
	}
}
