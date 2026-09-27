<?php
declare(strict_types=1);

namespace Customigniter\Core;

/**
 * .env File Loader
 *
 * Parses KEY=VALUE pairs from a .env file and exposes them to the
 * runtime environment. Real environment variables always take
 * precedence - values defined outside the process are never
 * overwritten, so platform-level configuration wins.
 *
 * Supported syntax:
 *   KEY=value             # bare value (inline comments stripped)
 *   KEY="value"           # double-quoted (escapes: \n \r \t \\ \")
 *   KEY='value'           # single-quoted (literal)
 *   export KEY=value      # optional export prefix
 *
 * Loaded values are published to putenv() and $_ENV so getenv(),
 * $_ENV and the env() helper all see them.
 *
 * @package	Customigniter3
 */
final class EnvLoader
{
	/**
	 * Values parsed from the .env file (key => value)
	 *
	 * @var array<string, string>
	 */
	private static array $values = [];

	/**
	 * Keys whose source value was quoted (casting must be skipped)
	 *
	 * @var array<string, true>
	 */
	private static array $quoted = [];

	/**
	 * Whether a load has been attempted (even if no file existed)
	 *
	 * @var bool
	 */
	private static bool $loaded = false;

	// --------------------------------------------------------------------

	/**
	 * Load a .env file from a directory
	 *
	 * Idempotent-safe: called explicitly by the front controller and the
	 * CLI entry point; env() triggers a lazy attempt from FCPATH when
	 * neither has run yet. Existing environment variables are kept.
	 *
	 * @param	string	$directory	Directory that may contain a .env file
	 * @return	bool	TRUE when a file was found and parsed
	 */
	public static function load(string $directory): bool
	{
		self::$loaded = true;

		$file = rtrim($directory, '/\\').DIRECTORY_SEPARATOR.'.env';

		if ( ! is_file($file) OR ! is_readable($file))
		{
			return false;
		}

		$lines = file($file, FILE_IGNORE_NEW_LINES);

		if ($lines === false)
		{
			return false;
		}

		foreach ($lines as $line)
		{
			self::parseLine($line);
		}

		return true;
	}

	// --------------------------------------------------------------------

	/**
	 * Parse a single .env line and store it when applicable
	 *
	 * @param	string	$line
	 * @return	void
	 */
	private static function parseLine(string $line): void
	{
		$line = trim($line);

		if ($line === '' OR $line[0] === '#')
		{
			return;
		}

		if (str_starts_with($line, 'export '))
		{
			$line = ltrim(substr($line, 7));
		}

		$eq = strpos($line, '=');

		if ($eq === false)
		{
			return;
		}

		$key = trim(substr($line, 0, $eq));

		if (preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/', $key) !== 1)
		{
			return;
		}

		$rawValue = trim(substr($line, $eq + 1));
		$quoted = false;

		if (strlen($rawValue) >= 2)
		{
			$first = $rawValue[0];
			$last  = $rawValue[strlen($rawValue) - 1];

			if (($first === '"' AND $last === '"') OR ($first === "'" AND $last === "'"))
			{
				$quoted = true;
				$inner = substr($rawValue, 1, -1);

				if ($first === '"')
				{
					$inner = strtr($inner, [
						'\\"'  => '"',
						'\n'   => "\n",
						'\r'   => "\r",
						'\t'   => "\t",
						'\\\\' => '\\',
					]);
				}

				$rawValue = $inner;
			}
			else
			{
				// Strip trailing comments from unquoted values ("value # note").
				$hashPos = strpos($rawValue, ' #');

				if ($hashPos !== false)
				{
					$rawValue = rtrim(substr($rawValue, 0, $hashPos));
				}
			}
		}

		// Real environment variables always win over the file.
		if (getenv($key) !== false OR array_key_exists($key, $_ENV))
		{
			return;
		}

		self::$values[$key] = $rawValue;

		if ($quoted)
		{
			self::$quoted[$key] = true;
		}

		putenv($key.'='.$rawValue);
		$_ENV[$key] = $rawValue;
	}

	// --------------------------------------------------------------------

	/**
	 * Get a raw value from the loaded .env store
	 *
	 * @param	string	$key
	 * @return	string|null
	 */
	public static function get(string $key): ?string
	{
		if (array_key_exists($key, self::$values))
		{
			return self::$values[$key];
		}

		$value = getenv($key);

		return $value === false ? null : $value;
	}

	// --------------------------------------------------------------------

	/**
	 * Check whether a key's value was quoted in the .env file
	 *
	 * Quoted values are never cast by the env() helper.
	 *
	 * @param	string	$key
	 * @return	bool
	 */
	public static function isQuoted(string $key): bool
	{
		return isset(self::$quoted[$key]);
	}

	// --------------------------------------------------------------------

	/**
	 * Whether a load attempt has already been made
	 *
	 * @return	bool
	 */
	public static function isLoaded(): bool
	{
		return self::$loaded;
	}

	// --------------------------------------------------------------------

	/**
	 * Reset the loader state (used by tests)
	 *
	 * @return	void
	 */
	public static function reset(): void
	{
		foreach (array_keys(self::$values) as $key)
		{
			unset($_ENV[$key]);
			putenv($key);
		}

		self::$values = [];
		self::$quoted = [];
		self::$loaded = false;
	}
}
