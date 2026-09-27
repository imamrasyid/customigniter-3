<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use Customigniter\Core\Environment;

class EnvironmentTest extends TestCase
{
	// --------------------------------------------------------------------

	public function test_from_string_development(): void
	{
		$this->assertEquals(Environment::Development, Environment::fromString('development'));
	}

	// --------------------------------------------------------------------

	public function test_from_string_testing(): void
	{
		$this->assertEquals(Environment::Testing, Environment::fromString('testing'));
	}

	// --------------------------------------------------------------------

	public function test_from_string_production(): void
	{
		$this->assertEquals(Environment::Production, Environment::fromString('production'));
	}

	// --------------------------------------------------------------------

	public function test_from_string_invalid_defaults_to_production(): void
	{
		$this->assertEquals(Environment::Production, Environment::fromString('invalid'));
	}

	// --------------------------------------------------------------------

	public function test_is_development(): void
	{
		$this->assertTrue(Environment::Development->isDevelopment());
		$this->assertFalse(Environment::Testing->isDevelopment());
		$this->assertFalse(Environment::Production->isDevelopment());
	}

	// --------------------------------------------------------------------

	public function test_is_testing(): void
	{
		$this->assertTrue(Environment::Testing->isTesting());
		$this->assertFalse(Environment::Development->isTesting());
		$this->assertFalse(Environment::Production->isTesting());
	}

	// --------------------------------------------------------------------

	public function test_is_production(): void
	{
		$this->assertTrue(Environment::Production->isProduction());
		$this->assertFalse(Environment::Development->isProduction());
		$this->assertFalse(Environment::Testing->isProduction());
	}

	// --------------------------------------------------------------------

	public function test_error_reporting_level(): void
	{
		$this->assertEquals(E_ALL, Environment::Development->errorReportingLevel());
		$this->assertEquals(E_ALL & ~E_NOTICE & ~E_DEPRECATED, Environment::Testing->errorReportingLevel());
		$this->assertEquals(E_ALL & ~E_NOTICE & ~E_DEPRECATED & ~E_USER_NOTICE & ~E_USER_DEPRECATED, Environment::Production->errorReportingLevel());
	}

	// --------------------------------------------------------------------

	public function test_should_display_errors(): void
	{
		$this->assertTrue(Environment::Development->shouldDisplayErrors());
		$this->assertFalse(Environment::Testing->shouldDisplayErrors());
		$this->assertFalse(Environment::Production->shouldDisplayErrors());
	}

	// --------------------------------------------------------------------

	public function test_value_matches_string(): void
	{
		$this->assertEquals('development', Environment::Development->value);
		$this->assertEquals('testing', Environment::Testing->value);
		$this->assertEquals('production', Environment::Production->value);
	}
}
