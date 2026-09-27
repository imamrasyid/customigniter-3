<?php
declare(strict_types=1);

namespace Customigniter\Database;

/**
 * Seeder Runner
 *
 * Discovers and executes seeder classes from a directory
 * (application/database/seeds by default). Guards against
 * recursive seed loops.
 *
 * @package	Customigniter3
 */
final class SeederRunner
{
	/**
	 * Seeder classes currently executing (recursion guard)
	 *
	 * @var array<string, true>
	 */
	private array $running = [];

	// --------------------------------------------------------------------

	/**
	 * Constructor
	 *
	 * @param	\CI_DB_query_builder	$db
	 * @param	string	$seedPath	Directory containing seeder files
	 */
	public function __construct(
		private \CI_DB_query_builder $db,
		private string $seedPath
	) {
		$this->seedPath = rtrim($seedPath, '/\\').DIRECTORY_SEPARATOR;
	}

	// --------------------------------------------------------------------

	/**
	 * Execute a seeder by class name
	 *
	 * @param	string	$class	Seeder class name (file: {class}.php)
	 * @return	void
	 * @throws	\RuntimeException	When the file is missing, the class is invalid, or a loop is detected
	 */
	public function run(string $class): void
	{
		if (preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/', $class) !== 1)
		{
			throw new \InvalidArgumentException("Invalid seeder name: '{$class}'");
		}

		if (isset($this->running[$class]))
		{
			throw new \RuntimeException("Recursive seeder call detected: '{$class}'");
		}

		$file = $this->seedPath.$class.'.php';

		if ( ! is_file($file))
		{
			throw new \RuntimeException("Seeder file not found: {$file}");
		}

		require_once $file;

		if ( ! class_exists($class, FALSE))
		{
			throw new \RuntimeException("Seeder class '{$class}' not found in {$file}");
		}

		$seeder = new $class($this->db, $this);

		if ( ! $seeder instanceof Seeder)
		{
			throw new \RuntimeException("Seeder '{$class}' must extend ".Seeder::class);
		}

		$this->running[$class] = true;

		try
		{
			$seeder->run();
		}
		finally
		{
			unset($this->running[$class]);
		}
	}

	// --------------------------------------------------------------------

	/**
	 * List discoverable seeder class names (sorted by file name)
	 *
	 * @return	list<string>
	 */
	public function discover(): array
	{
		$files = glob($this->seedPath.'*.php');

		if ($files === FALSE)
		{
			return [];
		}

		sort($files, SORT_STRING);

		$classes = [];
		foreach ($files as $file)
		{
			$classes[] = basename($file, '.php');
		}

		return $classes;
	}
}
