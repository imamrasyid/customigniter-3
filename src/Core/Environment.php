<?php
declare(strict_types=1);

namespace Customigniter\Core;

/**
 * Application Environment Enum
 *
 * @package	Customigniter3
 */
enum Environment: string
{
	case Development = 'development';
	case Testing     = 'testing';
	case Production  = 'production';

	/**
	 * Check if we are in development mode
	 *
	 * @return	bool
	 */
	public function isDevelopment(): bool
	{
		return $this === self::Development;
	}

	/**
	 * Check if we are in testing mode
	 *
	 * @return	bool
	 */
	public function isTesting(): bool
	{
		return $this === self::Testing;
	}

	/**
	 * Check if we are in production mode
	 *
	 * @return	bool
	 */
	public function isProduction(): bool
	{
		return $this === self::Production;
	}

	/**
	 * Get the error reporting level for this environment
	 *
	 * @return	int
	 */
	public function errorReportingLevel(): int
	{
		return match ($this) {
			self::Development => E_ALL,
			self::Testing     => E_ALL & ~E_NOTICE & ~E_DEPRECATED,
			self::Production  => E_ALL & ~E_NOTICE & ~E_DEPRECATED & ~E_USER_NOTICE & ~E_USER_DEPRECATED,
		};
	}

	/**
	 * Check if errors should be displayed
	 *
	 * @return	bool
	 */
	public function shouldDisplayErrors(): bool
	{
		return $this->isDevelopment();
	}

	/**
	 * Create from string value
	 *
	 * @param	string	$value
	 * @return	static
	 */
	public static function fromString(string $value): static
	{
		return self::tryFrom($value) ?? self::Production;
	}
}
