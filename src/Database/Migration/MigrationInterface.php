<?php
declare(strict_types=1);

namespace Customigniter\Database\Migration;

/**
 * Migration Interface
 *
 * All custom migrations must implement this contract.
 *
 * @package	Customigniter3
 */
interface MigrationInterface
{
	/**
	 * Run the migration
	 *
	 * @param	SchemaBuilder	$schema
	 * @return	void
	 */
	public function up(SchemaBuilder $schema): void;

	/**
	 * Reverse the migration
	 *
	 * @param	SchemaBuilder	$schema
	 * @return	void
	 */
	public function down(SchemaBuilder $schema): void;

	/**
	 * Get the migration timestamp
	 *
	 * @return	int
	 */
	public function getTimestamp(): int;
}
