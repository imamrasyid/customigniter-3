<?php
declare(strict_types=1);

namespace Customigniter\Database\Migration;

/**
 * Migration Runner
 *
 * Manages migration lifecycle: discovery, execution, rollback.
 * Compatible with CI3's migration tracking table.
 *
 * @package	Customigniter3
 */
class MigrationRunner
{
	private \CI_DB_query_builder $db;
	private \CI_DB_forge $forge;
	private string $migrationPath;
	private string $migrationTable = 'migrations';

	public function __construct(
		\CI_DB_query_builder $db,
		\CI_DB_forge $forge,
		string $migrationPath
	) {
		$this->db = $db;
		$this->forge = $forge;
		$this->migrationPath = rtrim($migrationPath, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
	}

	// --------------------------------------------------------------------

	/**
	 * Ensure the migrations table exists
	 *
	 * @return	void
	 */
	public function ensureMigrationTable(): void
	{
		if ( ! $this->db->table_exists($this->migrationTable)) {
			$this->forge->add_field([
				'version' => [
					'type'       => 'BIGINT',
					'constraint' => 20,
					'unsigned'   => true,
				],
				'class' => [
					'type' => 'VARCHAR',
					'constraint' => 255,
				],
				'batch' => [
					'type'       => 'INT',
					'constraint' => 11,
					'unsigned'   => true,
				],
			]);
			$this->forge->add_key('version', true);
			$this->forge->create_table($this->migrationTable);
		}
	}

	// --------------------------------------------------------------------

	/**
	 * Discover all migration files
	 *
	 * @return	array<int, string>	Sorted list of migration file paths
	 */
	public function discover(): array
	{
		$files = glob($this->migrationPath . '*.php');

		if ($files === false) {
			return [];
		}

		sort($files, SORT_STRING);

		return $files;
	}

	// --------------------------------------------------------------------

	/**
	 * Load a migration class from a file
	 *
	 * @param	string	$file
	 * @return	MigrationInterface
	 */
	private function loadMigration(string $file): MigrationInterface
	{
		$className = basename($file, '.php');

		require_once $file;

		if ( ! class_exists($className, false)) {
			throw new \RuntimeException("Migration class '{$className}' not found in {$file}");
		}

		$instance = new $className();

		if ( ! $instance instanceof MigrationInterface) {
			throw new \RuntimeException("Migration '{$className}' must implement MigrationInterface");
		}

		return $instance;
	}

	// --------------------------------------------------------------------

	/**
	 * Get the current migration version
	 *
	 * @return	int
	 */
	public function getCurrentVersion(): int
	{
		$this->ensureMigrationTable();

		$row = $this->db->select('version')
			->order_by('version', 'DESC')
			->limit(1)
			->get($this->migrationTable)
			->row_array();

		return $row !== null ? (int) $row['version'] : 0;
	}

	// --------------------------------------------------------------------

	/**
	 * Get the current batch number
	 *
	 * @return	int
	 */
	public function getCurrentBatch(): int
	{
		$this->ensureMigrationTable();

		$row = $this->db->select('batch')
			->order_by('batch', 'DESC')
			->limit(1)
			->get($this->migrationTable)
			->row_array();

		return $row !== null ? (int) $row['batch'] : 0;
	}

	// --------------------------------------------------------------------

	/**
	 * Run all pending migrations
	 *
	 * @param	int	$targetVersion	Target version (0 = all)
	 * @return	array{migrated: int, version: int}
	 */
	public function migrate(int $targetVersion = 0): array
	{
		$this->ensureMigrationTable();

		$currentVersion = $this->getCurrentVersion();
		$files = $this->discover();
		$batch = $this->getCurrentBatch() + 1;
		$migrated = 0;
		$schema = new SchemaBuilder($this->db, $this->forge);

		foreach ($files as $file) {
			$migration = $this->loadMigration($file);
			$timestamp = $migration->getTimestamp();

			if ($timestamp <= $currentVersion) {
				continue;
			}

			if ($targetVersion > 0 && $timestamp > $targetVersion) {
				continue;
			}

			// Apply the migration and record it atomically so a failure
			// cannot leave the schema changed without a tracking row
			// (or vice versa). Note: some MySQL DDL statements cause an
			// implicit commit, but the tracking row itself stays safe.
			$this->db->trans_begin();

			try {
				$migration->up($schema);

				$this->db->insert($this->migrationTable, [
					'version' => $timestamp,
					'class'   => basename($file, '.php'),
					'batch'   => $batch,
				]);

				$this->db->trans_commit();
			}
			catch (\Throwable $e) {
				$this->db->trans_rollback();

				throw new \RuntimeException(
					"Migration '" . basename($file) . "' failed: " . $e->getMessage(),
					0,
					$e
				);
			}

			$migrated++;
		}

		return ['migrated' => $migrated, 'version' => $this->getCurrentVersion()];
	}

	// --------------------------------------------------------------------

	/**
	 * Rollback the last batch of migrations
	 *
	 * @param	int	$steps	Number of batches to roll back
	 * @return	array{rolled_back: int, version: int}
	 */
	public function rollback(int $steps = 1): array
	{
		$this->ensureMigrationTable();

		$currentBatch = $this->getCurrentBatch();
		$schema = new SchemaBuilder($this->db, $this->forge);
		$rolledBack = 0;

		for ($i = 0; $i < $steps; $i++) {
			$batch = $currentBatch - $i;

			if ($batch < 1) {
				break;
			}

			$rows = $this->db->where('batch', $batch)
				->order_by('version', 'DESC')
				->get($this->migrationTable)
				->result_array();

			foreach ($rows as $row) {
				$file = $this->migrationPath . $row['class'] . '.php';

				if ( ! file_exists($file)) {
					throw new \RuntimeException("Migration file not found: {$file}");
				}

				$migration = $this->loadMigration($file);

				$this->db->trans_begin();

				try {
					$migration->down($schema);

					$this->db->where('version', $row['version'])
						->delete($this->migrationTable);

					$this->db->trans_commit();
				}
				catch (\Throwable $e) {
					$this->db->trans_rollback();

					throw new \RuntimeException(
						"Rollback of migration '" . $row['class'] . "' failed: " . $e->getMessage(),
						0,
						$e
					);
				}

				$rolledBack++;
			}
		}

		return ['rolled_back' => $rolledBack, 'version' => $this->getCurrentVersion()];
	}

	// --------------------------------------------------------------------

	/**
	 * Get migration status
	 *
	 * @return	array<int, array{file: string, class: string, version: int, status: string, batch: ?int}>
	 */
	public function getStatus(): array
	{
		$this->ensureMigrationTable();

		$files = $this->discover();
		$applied = [];

		$rows = $this->db->get($this->migrationTable)->result_array();
		foreach ($rows as $row) {
			$applied[$row['class']] = [
				'version' => (int) $row['version'],
				'batch'   => (int) $row['batch'],
			];
		}

		$status = [];
		$lastVersion = $this->getCurrentVersion();

		foreach ($files as $file) {
			$class = basename($file, '.php');
			$migration = $this->loadMigration($file);
			$timestamp = $migration->getTimestamp();

			if (isset($applied[$class])) {
				$status[] = [
					'file'    => basename($file),
					'class'   => $class,
					'version' => $timestamp,
					'status'  => 'applied',
					'batch'   => $applied[$class]['batch'],
				];
			}
			else {
				$status[] = [
					'file'    => basename($file),
					'class'   => $class,
					'version' => $timestamp,
					'status'  => $timestamp <= $lastVersion ? 'skipped' : 'pending',
					'batch'   => null,
				];
			}
		}

		return $status;
	}
}
