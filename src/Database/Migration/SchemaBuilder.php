<?php
declare(strict_types=1);

namespace Customigniter\Database\Migration;

/**
 * Schema Builder
 *
 * Fluent API for building database schema changes within migrations.
 * Wraps CI3's DB_forge with a cleaner, more expressive syntax.
 *
 * @package	Customigniter3
 */
class SchemaBuilder
{
	private \CI_DB_query_builder $db;
	private \CI_DB_forge $forge;

	public function __construct(\CI_DB_query_builder $db, \CI_DB_forge $forge)
	{
		$this->db = $db;
		$this->forge = $forge;
	}

	// --------------------------------------------------------------------
	// Table Operations
	// --------------------------------------------------------------------

	/**
	 * Create a new table
	 *
	 * @param	string	$table
	 * @param callable(TableBuilder): void $callback
	 * @return	void
	 */
	public function create(string $table, callable $callback): void
	{
		$builder = new TableBuilder($table);
		$callback($builder);

		$this->forge->add_field($builder->getFields());
		$this->forge->add_key($builder->getPrimaryKeys(), true);

		if ( ! empty($builder->getUniqueKeys())) {
			foreach ($builder->getUniqueKeys() as $key) {
				$this->forge->add_key($key, false, true);
			}
		}

		if ( ! empty($builder->getIndexes())) {
			foreach ($builder->getIndexes() as $index) {
				$this->forge->add_key($index);
			}
		}

		$this->forge->create_table($table);
	}

	// --------------------------------------------------------------------

	/**
	 * Drop a table
	 *
	 * @param	string	$table
	 * @return	void
	 */
	public function drop(string $table): void
	{
		$this->forge->drop_table($table);
	}

	// --------------------------------------------------------------------

	/**
	 * Check if a table exists
	 *
	 * @param	string	$table
	 * @return	bool
	 */
	public function hasTable(string $table): bool
	{
		return $this->db->table_exists($table);
	}

	// --------------------------------------------------------------------
	// Column Operations
	// --------------------------------------------------------------------

	/**
	 * Add a column to a table
	 *
	 * @param	string	$table
	 * @param	string	$column
	 * @param array<string, mixed> $params
	 * @return	void
	 */
	public function addColumn(string $table, string $column, array $params): void
	{
		$this->forge->add_column($table, [$column => $params]);
	}

	// --------------------------------------------------------------------

	/**
	 * Drop a column from a table
	 *
	 * @param	string	$table
	 * @param	string	$column
	 * @return	void
	 */
	public function dropColumn(string $table, string $column): void
	{
		$this->forge->drop_column($table, $column);
	}

	// --------------------------------------------------------------------

	/**
	 * Modify a column
	 *
	 * @param	string	$table
	 * @param	string	$column
	 * @param array<string, mixed> $params
	 * @return	void
	 */
	public function modifyColumn(string $table, string $column, array $params): void
	{
		$this->forge->modify_column($table, [$column => $params]);
	}

	// --------------------------------------------------------------------

	/**
	 * Rename a column
	 *
	 * @param	string	$table
	 * @param	string	$oldName
	 * @param	string	$newName
	 * @return	void
	 */
	public function renameColumn(string $table, string $oldName, string $newName): void
	{
		$this->forge->rename_column($table, $oldName, $newName);
	}

	// --------------------------------------------------------------------

	/**
	 * Check if a column exists
	 *
	 * @param	string	$table
	 * @param	string	$column
	 * @return	bool
	 */
	public function hasColumn(string $table, string $column): bool
	{
		return $this->db->field_exists($column, $table);
	}

	// --------------------------------------------------------------------
	// Index Operations
	// --------------------------------------------------------------------

	/**
	 * Add an index to an existing table
	 *
	 * Executes CREATE INDEX directly against the database. For indexes
	 * created together with a new table, use TableBuilder::index() or
	 * TableBuilder::unique() inside create() instead.
	 *
	 * @param	string	$table
	 * @param	string|string[]	$column	One or more column names
	 * @param	bool	$unique	Create a UNIQUE index
	 * @return	void
	 * @throws	\RuntimeException	When the index cannot be created
	 */
	public function addIndex(string $table, string|array $column, bool $unique = false): void
	{
		$columns = array_values(array_filter((array) $column, 'is_string'));

		if ($columns === []) {
			throw new \InvalidArgumentException('At least one column name is required to create an index.');
		}

		$prefix = $this->db->dbprefix;
		$fullTable = $prefix.$table;
		$indexName = $prefix.$table.'_'.implode('_', $columns);

		$sql = 'CREATE '.($unique ? 'UNIQUE ' : '')
			.'INDEX '.$this->db->escape_identifiers($indexName)
			.' ON '.$this->db->escape_identifiers($fullTable)
			.' ('.implode(', ', $this->db->escape_identifiers($columns)).')';

		if ($this->db->query($sql) === FALSE) {
			throw new \RuntimeException("Failed to create index '{$indexName}' on table '{$fullTable}'.");
		}
	}

	// --------------------------------------------------------------------

	/**
	 * Drop an index
	 *
	 * @param	string	$table
	 * @param	string	$column
	 * @return	void
	 */
	public function dropIndex(string $table, string $column): void
	{
		$this->forge->drop_key($table, $column);
	}

	// --------------------------------------------------------------------
	// Raw Access
	// --------------------------------------------------------------------

	/**
	 * Get the underlying DB query builder
	 *
	 * @return	\CI_DB_query_builder
	 */
	public function getDb(): \CI_DB_query_builder
	{
		return $this->db;
	}

	/**
	 * Get the underlying DB forge
	 *
	 * @return	\CI_DB_forge
	 */
	public function getForge(): \CI_DB_forge
	{
		return $this->forge;
	}
}
