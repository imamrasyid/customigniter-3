<?php
declare(strict_types=1);

namespace Customigniter\Database;

/**
 * Database Driver Enum
 *
 * Lists supported database drivers and their capabilities.
 *
 * @package	Customigniter3
 */
enum DatabaseDriver: string
{
	case MySQL      = 'mysqli';
	case PostgreSQL = 'postgre';
	case SQLite     = 'sqlite';
	case SQLite3    = 'sqlite3';

	/**
	 * Get the driver's display name
	 *
	 * @return	string
	 */
	public function displayName(): string
	{
		return match ($this) {
			self::MySQL      => 'MySQL',
			self::PostgreSQL => 'PostgreSQL',
			self::SQLite     => 'SQLite',
			self::SQLite3    => 'SQLite3',
		};
	}

	/**
	 * Get the default port for this driver
	 *
	 * @return	int
	 */
	public function defaultPort(): int
	{
		return match ($this) {
			self::MySQL      => 3306,
			self::PostgreSQL => 5432,
			self::SQLite     => 0,
			self::SQLite3    => 0,
		};
	}

	/**
	 * Check if the driver supports transactions
	 *
	 * @return	bool
	 */
	public function supportsTransactions(): bool
	{
		return match ($this) {
			self::MySQL      => true,
			self::PostgreSQL => true,
			self::SQLite     => true,
			self::SQLite3    => true,
		};
	}

	/**
	 * Check if the driver supports savepoints
	 *
	 * @return	bool
	 */
	public function supportsSavepoints(): bool
	{
		return match ($this) {
			self::MySQL      => true,
			self::PostgreSQL => true,
			self::SQLite     => true,
			self::SQLite3    => true,
		};
	}

	/**
	 * Check if the driver supports upsert (INSERT ... ON DUPLICATE KEY / ON CONFLICT)
	 *
	 * @return	bool
	 */
	public function supportsUpsert(): bool
	{
		return match ($this) {
			self::MySQL      => true,
			self::PostgreSQL => true, // ON CONFLICT
			self::SQLite     => false,
			self::SQLite3    => false,
		};
	}

	/**
	 * Check if the driver supports JSON column type
	 *
	 * @return	bool
	 */
	public function supportsJsonColumns(): bool
	{
		return match ($this) {
			self::MySQL      => true, // MySQL 5.7+
			self::PostgreSQL => true,
			self::SQLite     => false,
			self::SQLite3    => false, // TEXT-based workaround
		};
	}

	/**
	 * Check if the driver supports full-text search
	 *
	 * @return	bool
	 */
	public function supportsFullText(): bool
	{
		return match ($this) {
			self::MySQL      => true,
			self::PostgreSQL => true, // tsvector/tsquery
			self::SQLite     => false,
			self::SQLite3    => false,
		};
	}
}
