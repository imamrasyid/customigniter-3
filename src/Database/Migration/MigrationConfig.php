<?php
declare(strict_types=1);

namespace Customigniter\Database\Migration;

/**
 * Migration Config Loader
 *
 * Reads application/config/migration.php into a typed value without
 * letting the untracked include() leak untyped variables into the
 * calling commands.
 *
 * @package	Customigniter3
 */
final class MigrationConfig
{
	// --------------------------------------------------------------------

	/**
	 * Load migration path and tracking table from a config file
	 *
	 * @param	string	$file
	 * @param	string	$defaultPath
	 * @param	string	$defaultTable
	 * @return	array{path: string, table: string}
	 */
	public static function load(string $file, string $defaultPath, string $defaultTable): array
	{
		$config = self::readConfig($file);

		$path = $config['migration_path'] ?? null;
		$table = $config['migration_table'] ?? null;

		return [
			'path'  => rtrim((is_string($path) AND $path !== '') ? $path : $defaultPath, '/\\'),
			'table' => (is_string($table) AND $table !== '') ? $table : $defaultTable,
		];
	}

	// --------------------------------------------------------------------

	/**
	 * Include the config file and hand its values back typed
	 *
	 * The include is only visible to the analyser through the declared
	 * return type, so callers never see the untracked assignment.
	 *
	 * @param	string	$file
	 * @return	array<string, mixed>
	 */
	private static function readConfig(string $file): array
	{
		$config = [];

		if (is_file($file))
		{
			include $file;
		}

		return $config;
	}
}
