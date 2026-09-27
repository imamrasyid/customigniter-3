<?php
declare(strict_types=1);

namespace Customigniter\Database;

/**
 * Database Connection Pair
 *
 * Bundles the query builder with the matching forge instance for
 * use by console commands (migrations, seeds).
 *
 * @package	Customigniter3
 */
final class DatabaseConnection
{
	/**
	 * Constructor
	 *
	 * @param	\CI_DB_query_builder	$db
	 * @param	\CI_DB_forge	$forge
	 */
	public function __construct(
		public \CI_DB_query_builder $db,
		public \CI_DB_forge $forge
	) {
	}
}
