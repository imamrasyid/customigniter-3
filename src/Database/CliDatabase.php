<?php
declare(strict_types=1);

namespace Customigniter\Database;

/**
 * Standalone Database Connector
 *
 * Creates a query builder + forge pair outside the full framework
 * bootstrap, so console commands can run migrations and seeds
 * without a running request. Mirrors CI_Loader::dbforge() driver
 * resolution, including PDO subdrivers.
 *
 * @package	Customigniter3
 */
final class CliDatabase
{
	// --------------------------------------------------------------------

	/**
	 * Open a connection using the application's database config
	 *
	 * @param	string	$group	Connection group from database.php ('' = $active_group)
	 * @return	DatabaseConnection
	 * @throws	\RuntimeException	When the connection or forge cannot be created
	 */
	public static function connect(string $group = ''): DatabaseConnection
	{
		if ( ! function_exists('DB'))
		{
			require_once BASEPATH.'database/DB.php';
		}

		$db = &DB($group);

		if ( ! $db instanceof \CI_DB_query_builder)
		{
			throw new \RuntimeException('The database connection did not return a query builder instance.');
		}

		return new DatabaseConnection($db, self::forge($db));
	}

	// --------------------------------------------------------------------

	/**
	 * Build the forge class matching the connected driver
	 *
	 * Mirrors CI_Loader::dbforge(): base forge file first, then the
	 * driver file, then the subdriver file when one is active.
	 *
	 * @param	\CI_DB_query_builder	$db
	 * @return	\CI_DB_forge
	 * @throws	\RuntimeException
	 */
	private static function forge(\CI_DB_query_builder $db): \CI_DB_forge
	{
		require_once BASEPATH.'database/DB_forge.php';

		$forgeFile = BASEPATH.'database/drivers/'.$db->dbdriver.'/'.$db->dbdriver.'_forge.php';

		if ( ! is_file($forgeFile))
		{
			throw new \RuntimeException("No forge implementation found for driver '{$db->dbdriver}'.");
		}

		require_once $forgeFile;
		$class = 'CI_DB_'.$db->dbdriver.'_forge';

		if ( ! empty($db->subdriver))
		{
			$subFile = BASEPATH.'database/drivers/'.$db->dbdriver.'/subdrivers/'
				.$db->dbdriver.'_'.$db->subdriver.'_forge.php';

			if (is_file($subFile))
			{
				require_once $subFile;
				$class = 'CI_DB_'.$db->dbdriver.'_'.$db->subdriver.'_forge';
			}
		}

		if ( ! class_exists($class, FALSE))
		{
			throw new \RuntimeException("Forge class '{$class}' was not found.");
		}

		$forge = new $class($db);

		if ( ! $forge instanceof \CI_DB_forge)
		{
			throw new \RuntimeException("Class '{$class}' is not a CI_DB_forge implementation.");
		}

		return $forge;
	}
}
