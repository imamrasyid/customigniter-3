<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use Customigniter\Core\Environment;

class EnvironmentTest extends TestCase
{
	// --------------------------------------------------------------------

	public function test_from_string_development()
	{
		$this->assertEquals(Environment::Development, Environment::fromString('development'));
	}

	// --------------------------------------------------------------------

	public function test_from_string_testing()
	{
		$this->assertEquals(Environment::Testing, Environment::fromString('testing'));
	}

	// --------------------------------------------------------------------

	public function test_from_string_production()
	{
		$this->assertEquals(Environment::Production, Environment::fromString('production'));
	}

	// --------------------------------------------------------------------

	public function test_from_string_invalid_defaults_to_production()
	{
		$this->assertEquals(Environment::Production, Environment::fromString('invalid'));
	}

	// --------------------------------------------------------------------

	public function test_is_development()
	{
		$this->assertTrue(Environment::Development->isDevelopment());
		$this->assertFalse(Environment::Testing->isDevelopment());
		$this->assertFalse(Environment::Production->isDevelopment());
	}

	// --------------------------------------------------------------------

	public function test_is_testing()
	{
		$this->assertTrue(Environment::Testing->isTesting());
		$this->assertFalse(Environment::Development->isTesting());
		$this->assertFalse(Environment::Production->isTesting());
	}

	// --------------------------------------------------------------------

	public function test_is_production()
	{
		$this->assertTrue(Environment::Production->isProduction());
		$this->assertFalse(Environment::Development->isProduction());
		$this->assertFalse(Environment::Testing->isProduction());
	}

	// --------------------------------------------------------------------

	public function test_error_reporting_level()
	{
		$this->assertEquals(E_ALL, Environment::Development->errorReportingLevel());
		$this->assertIsInt(Environment::Testing->errorReportingLevel());
		$this->assertIsInt(Environment::Production->errorReportingLevel());
	}

	// --------------------------------------------------------------------

	public function test_should_display_errors()
	{
		$this->assertTrue(Environment::Development->shouldDisplayErrors());
		$this->assertFalse(Environment::Testing->shouldDisplayErrors());
		$this->assertFalse(Environment::Production->shouldDisplayErrors());
	}

	// --------------------------------------------------------------------

	public function test_value_matches_string()
	{
		$this->assertEquals('development', Environment::Development->value);
		$this->assertEquals('testing', Environment::Testing->value);
		$this->assertEquals('production', Environment::Production->value);
	}
}
