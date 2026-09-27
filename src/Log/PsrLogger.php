<?php
declare(strict_types=1);

namespace Customigniter\Log;

use Psr\Log\AbstractLogger;
use Psr\Log\LogLevel;

/**
 * PSR-3 Logger Adapter
 *
 * Bridges PSR-3 LoggerInterface to CodeIgniter's CI_Log logging system.
 *
 * @package	Customigniter3
 */
class PsrLogger extends AbstractLogger
{
	/**
	 * PSR-3 level to CI log level mapping
	 *
	 * @var array<string, string>
	 */
	private const LEVEL_MAP = [
		LogLevel::EMERGENCY => 'error',
		LogLevel::ALERT     => 'error',
		LogLevel::CRITICAL  => 'error',
		LogLevel::ERROR     => 'error',
		LogLevel::WARNING   => 'warning',
		LogLevel::NOTICE    => 'notice',
		LogLevel::INFO      => 'info',
		LogLevel::DEBUG     => 'debug',
	];

	// --------------------------------------------------------------------

	/**
	 * Log a message at the given level
	 *
	 * @param	string			$level
	 * @param	string|\Stringable	$message
	 * @param	array<string, mixed>	$context
	 * @return	void
	 */
	public function log($level, string|\Stringable $message, array $context = []): void
	{
		// Unknown levels must not silently degrade to 'info'.
		$ciLevel = self::LEVEL_MAP[$level] ?? 'error';

		$compiledMessage = $this->interpolate($message, $context);

		if (function_exists('log_message')) {
			log_message($ciLevel, $compiledMessage);
		}
	}

	// --------------------------------------------------------------------

	/**
	 * Interpolate context values into the message
	 *
	 * Replaces {key} placeholders with context values, PSR-3 style.
	 *
	 * @param	string|\Stringable	$message
	 * @param	array<string, mixed>	$context
	 * @return	string
	 */
	private function interpolate(string|\Stringable $message, array $context): string
	{
		$replace = [];

		foreach ($context as $key => $val) {
			if (is_string($val) || (is_object($val) && method_exists($val, '__toString'))) {
				$replace['{' . $key . '}'] = (string) $val;
			}
		}

		return strtr((string) $message, $replace);
	}
}
