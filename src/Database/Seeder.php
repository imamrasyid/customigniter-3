<?php
declare(strict_types=1);

namespace Customigniter\Database;

/**
 * Base Seeder
 *
 * Subclass and implement run() to populate database tables with
 * initial data. Seeders live in application/database/seeds/ and are
 * executed by the db:seed console command via SeederRunner.
 *
 * @package	Customigniter3
 */
abstract class Seeder
{
	/**
	 * Constructor
	 *
	 * Injected by SeederRunner. Call parent::__construct() when
	 * overriding.
	 *
	 * @param	\CI_DB_query_builder|null	$db
	 * @param	SeederRunner|null	$runner	Runner used to compose seeders via call()
	 */
	public function __construct(
		protected ?\CI_DB_query_builder $db = NULL,
		protected ?SeederRunner $runner = NULL
	) {
	}

	// --------------------------------------------------------------------

	/**
	 * Populate the database
	 *
	 * @return	void
	 */
	abstract public function run(): void;

	// --------------------------------------------------------------------

	/**
	 * Get the database connection
	 *
	 * @return	\CI_DB_query_builder
	 * @throws	\RuntimeException	When the seeder was not run by a SeederRunner
	 */
	protected function db(): \CI_DB_query_builder
	{
		if ($this->db === NULL)
		{
			throw new \RuntimeException(
				'Seeders can only use db() when executed by a SeederRunner.'
			);
		}

		return $this->db;
	}

	// --------------------------------------------------------------------

	/**
	 * Run another seeder from within this one
	 *
	 * @param	string	$seeder	Seeder class name
	 * @return	void
	 * @throws	\RuntimeException	When no runner is available (recursive call loop is detected by the runner)
	 */
	protected function call(string $seeder): void
	{
		if ($this->runner === NULL)
		{
			throw new \RuntimeException(
				"Seeder '{$seeder}' can only be call()ed while a SeederRunner is active."
			);
		}

		$this->runner->run($seeder);
	}
}
