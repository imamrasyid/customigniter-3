<?php
declare(strict_types=1);

namespace Customigniter\Console;

/**
 * Base CLI Command
 *
 * Provides output helpers and option parsing for CLI commands.
 *
 * @package	Customigniter3
 */
abstract class Command implements CommandInterface
{
	/**
	 * ANSI color codes
	 */
	private const COLORS = [
		'red'     => "\033[31m",
		'green'   => "\033[32m",
		'yellow'  => "\033[33m",
		'blue'    => "\033[34m",
		'magenta' => "\033[35m",
		'cyan'    => "\033[36m",
		'white'   => "\033[37m",
		'bold'    => "\033[1m",
		'reset'   => "\033[0m",
	];

	// --------------------------------------------------------------------

	/**
	 * Write a line to stdout
	 *
	 * @param	string	$message
	 * @return	void
	 */
	protected function info(string $message): void
	{
		fwrite(STDOUT, $message . PHP_EOL);
	}

	// --------------------------------------------------------------------

	/**
	 * Write a success message
	 *
	 * @param	string	$message
	 * @return	void
	 */
	protected function success(string $message): void
	{
		fwrite(STDOUT, self::COLORS['green'] . $message . self::COLORS['reset'] . PHP_EOL);
	}

	// --------------------------------------------------------------------

	/**
	 * Write a warning message
	 *
	 * @param	string	$message
	 * @return	void
	 */
	protected function warn(string $message): void
	{
		fwrite(STDERR, self::COLORS['yellow'] . $message . self::COLORS['reset'] . PHP_EOL);
	}

	// --------------------------------------------------------------------

	/**
	 * Write an error message
	 *
	 * @param	string	$message
	 * @return	void
	 */
	protected function error(string $message): void
	{
		fwrite(STDERR, self::COLORS['red'] . $message . self::COLORS['reset'] . PHP_EOL);
	}

	// --------------------------------------------------------------------

	/**
	 * Write a formatted header
	 *
	 * @param	string	$title
	 * @return	void
	 */
	protected function header(string $title): void
	{
		fwrite(STDOUT, PHP_EOL . self::COLORS['bold'] . self::COLORS['cyan'] . $title . self::COLORS['reset'] . PHP_EOL);
	}

	// --------------------------------------------------------------------

	/**
	 * Ask a yes/no question
	 *
	 * @param	string	$question
	 * @param	bool	$default
	 * @return	bool
	 */
	protected function confirm(string $question, bool $default = true): bool
	{
		$hint = $default ? '[Y/n]' : '[y/N]';
		fwrite(STDOUT, $question . ' ' . $hint . ' ');

		$input = trim((string) fgets(STDIN));

		if ($input === '') {
			return $default;
		}

		return in_array(strtolower($input), ['y', 'yes'], true);
	}

	// --------------------------------------------------------------------

	/**
	 * Parse flags from arguments
	 *
	 * Extracts --key=value and --key value pairs; bare --flag tokens are
	 * returned as flags. A bare --flag followed by a non-dash token is
	 * treated as an option taking that value (standard CLI convention).
	 *
	 * @param	array<int, string>	$args
	 * @return	array{flags: array<int, string>, options: array<string, string>, args: array<int, string>}
	 */
	protected function parseOptions(array $args): array
	{
		$flags = [];
		$options = [];
		$positional = [];

		$count = count($args);
		for ($i = 0; $i < $count; $i++) {
			$arg = $args[$i];

			if (str_starts_with($arg, '--')) {
				$eqPos = strpos($arg, '=');
				if ($eqPos !== false) {
					$key = substr($arg, 2, $eqPos - 2);
					$options[$key] = substr($arg, $eqPos + 1);
				}
				elseif ($i + 1 < $count && ! str_starts_with($args[$i + 1], '-')) {
					$options[substr($arg, 2)] = $args[++$i];
				}
				else {
					$flags[] = substr($arg, 2);
				}
			}
			else {
				$positional[] = $arg;
			}
		}

		return ['flags' => $flags, 'options' => $options, 'args' => $positional];
	}

	// --------------------------------------------------------------------

	/**
	 * Check if a flag is set
	 *
	 * @param	array{flags: array<int, string>, options: array<string, string>, args: array<int, string>}	$parsed
	 * @param	string	$flag
	 * @return	bool
	 */
	protected function hasFlag(array $parsed, string $flag): bool
	{
		return in_array($flag, $parsed['flags'], true);
	}

	// --------------------------------------------------------------------

	/**
	 * Get an option value with default
	 *
	 * @param	array{flags: array<int, string>, options: array<string, string>, args: array<int, string>}	$parsed
	 * @param	string	$key
	 * @param	string	$default
	 * @return	string
	 */
	protected function getOption(array $parsed, string $key, string $default = ''): string
	{
		return $parsed['options'][$key] ?? $default;
	}
}
