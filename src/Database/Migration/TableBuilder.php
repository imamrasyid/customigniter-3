<?php
declare(strict_types=1);

namespace Customigniter\Database\Migration;

/**
 * Table Builder
 *
 * Fluent API for defining table columns during migration create().
 *
 * @package	Customigniter3
 */
class TableBuilder
{
	private string $tableName;
	private array $fields = [];
	private array $primaryKeys = [];
	private array $uniqueKeys = [];
	private array $indexes = [];

	public function __construct(string $tableName)
	{
		$this->tableName = $tableName;
	}

	// --------------------------------------------------------------------
	// Column Definitions
	// --------------------------------------------------------------------

	/**
	 * Add an auto-incrementing integer primary key
	 *
	 * @param	string	$name
	 * @return	static
	 */
	public function id(string $name = 'id'): static
	{
		$this->fields[$name] = [
			'type'       => 'INT',
			'constraint' => 11,
			'unsigned'   => true,
			'auto_increment' => true,
		];
		$this->primaryKeys[] = $name;

		return $this;
	}

	// --------------------------------------------------------------------

	/**
	 * Add a string/varchar column
	 *
	 * @param	string	$name
	 * @param	int	$length
	 * @param	bool	$nullable
	 * @return	static
	 */
	public function string(string $name, int $length = 255, bool $nullable = false): static
	{
		$this->fields[$name] = [
			'type'       => 'VARCHAR',
			'constraint' => $length,
			'null'       => $nullable,
		];

		return $this;
	}

	// --------------------------------------------------------------------

	/**
	 * Add a text column
	 *
	 * @param	string	$name
	 * @param	bool	$nullable
	 * @return	static
	 */
	public function text(string $name, bool $nullable = false): static
	{
		$this->fields[$name] = [
			'type' => 'TEXT',
			'null' => $nullable,
		];

		return $this;
	}

	// --------------------------------------------------------------------

	/**
	 * Add an integer column
	 *
	 * @param	string	$name
	 * @param	int	$length
	 * @param	bool	$unsigned
	 * @param	bool	$nullable
	 * @return	static
	 */
	public function integer(string $name, int $length = 11, bool $unsigned = false, bool $nullable = false): static
	{
		$this->fields[$name] = [
			'type'       => 'INT',
			'constraint' => $length,
			'unsigned'   => $unsigned,
			'null'       => $nullable,
		];

		return $this;
	}

	// --------------------------------------------------------------------

	/**
	 * Add a big integer column
	 *
	 * @param	string	$name
	 * @param	bool	$unsigned
	 * @param	bool	$nullable
	 * @return	static
	 */
	public function bigInteger(string $name, bool $unsigned = false, bool $nullable = false): static
	{
		$this->fields[$name] = [
			'type'     => 'BIGINT',
			'unsigned' => $unsigned,
			'null'     => $nullable,
		];

		return $this;
	}

	// --------------------------------------------------------------------

	/**
	 * Add a float column
	 *
	 * @param	string	$name
	 * @param	int	$decimals
	 * @param	bool	$nullable
	 * @return	static
	 */
	public function float(string $name, int $decimals = 2, bool $nullable = false): static
	{
		$this->fields[$name] = [
			'type'       => 'FLOAT',
			'constraint' => $decimals,
			'null'       => $nullable,
		];

		return $this;
	}

	// --------------------------------------------------------------------

	/**
	 * Add a decimal column
	 *
	 * @param	string	$name
	 * @param	int	$total
	 * @param	int	$places
	 * @param	bool	$nullable
	 * @return	static
	 */
	public function decimal(string $name, int $total, int $places, bool $nullable = false): static
	{
		$this->fields[$name] = [
			'type'       => 'DECIMAL',
			'constraint' => "{$total},{$places}",
			'null'       => $nullable,
		];

		return $this;
	}

	// --------------------------------------------------------------------

	/**
	 * Add a boolean/tinyint column
	 *
	 * @param	string	$name
	 * @param	bool	$default
	 * @return	static
	 */
	public function boolean(string $name, bool $default = false): static
	{
		$this->fields[$name] = [
			'type'    => 'TINYINT',
			'constraint' => 1,
			'default' => $default ? 1 : 0,
		];

		return $this;
	}

	// --------------------------------------------------------------------

	/**
	 * Add a datetime column
	 *
	 * @param	string	$name
	 * @param	bool	$nullable
	 * @return	static
	 */
	public function datetime(string $name, bool $nullable = false): static
	{
		$this->fields[$name] = [
			'type' => 'DATETIME',
			'null' => $nullable,
		];

		return $this;
	}

	// --------------------------------------------------------------------

	/**
	 * Add a timestamp column
	 *
	 * @param	string	$name
	 * @param	bool	$useCurrent
	 * @return	static
	 */
	public function timestamp(string $name, bool $useCurrent = true): static
	{
		$this->fields[$name] = [
			'type'    => 'TIMESTAMP',
			'default' => $useCurrent ? 'CURRENT_TIMESTAMP' : null,
		];

		return $this;
	}

	// --------------------------------------------------------------------

	/**
	 * Add a JSON column
	 *
	 * @param	string	$name
	 * @param	bool	$nullable
	 * @return	static
	 */
	public function json(string $name, bool $nullable = false): static
	{
		$this->fields[$name] = [
			'type' => 'JSON',
			'null' => $nullable,
		];

		return $this;
	}

	// --------------------------------------------------------------------
	// Key Definitions
	// --------------------------------------------------------------------

	/**
	 * Mark a column as part of a composite primary key
	 *
	 * @param	string	$name
	 * @return	static
	 */
	public function primary(string $name): static
	{
		$this->primaryKeys[] = $name;

		return $this;
	}

	// --------------------------------------------------------------------

	/**
	 * Mark a column as a unique key
	 *
	 * @param	string	$name
	 * @return	static
	 */
	public function unique(string $name): static
	{
		$this->uniqueKeys[] = $name;

		return $this;
	}

	// --------------------------------------------------------------------

	/**
	 * Mark a column as an index
	 *
	 * @param	string	$name
	 * @return	static
	 */
	public function index(string $name): static
	{
		$this->indexes[] = $name;

		return $this;
	}

	// --------------------------------------------------------------------

	/**
	 * Set a column default value
	 *
	 * @param	string	$name
	 * @param	mixed	$value
	 * @return	static
	 */
	public function default(string $name, mixed $value): static
	{
		if (isset($this->fields[$name])) {
			$this->fields[$name]['default'] = $value;
		}

		return $this;
	}

	// --------------------------------------------------------------------

	/**
	 * Set a column as nullable
	 *
	 * @param	string	$name
	 * @return	static
	 */
	public function nullable(string $name): static
	{
		if (isset($this->fields[$name])) {
			$this->fields[$name]['null'] = true;
		}

		return $this;
	}

	// --------------------------------------------------------------------
	// Getters
	// --------------------------------------------------------------------

	public function getFields(): array { return $this->fields; }
	public function getPrimaryKeys(): array { return $this->primaryKeys; }
	public function getUniqueKeys(): array { return $this->uniqueKeys; }
	public function getIndexes(): array { return $this->indexes; }
}
