<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use Customigniter\Database\DatabaseDriver;

class DatabaseDriverTest extends TestCase
{
	// --------------------------------------------------------------------

	public function test_from_string_mysqli()
	{
		$this->assertEquals(DatabaseDriver::MySQL, DatabaseDriver::tryFrom('mysqli'));
	}

	// --------------------------------------------------------------------

	public function test_from_string_invalid()
	{
		$this->assertNull(DatabaseDriver::tryFrom('invalid'));
	}

	// --------------------------------------------------------------------

	public function test_display_name()
	{
		$this->assertEquals('MySQL', DatabaseDriver::MySQL->displayName());
		$this->assertEquals('PostgreSQL', DatabaseDriver::PostgreSQL->displayName());
		$this->assertEquals('SQLite', DatabaseDriver::SQLite->displayName());
		$this->assertEquals('SQLite3', DatabaseDriver::SQLite3->displayName());
	}

	// --------------------------------------------------------------------

	public function test_default_port()
	{
		$this->assertEquals(3306, DatabaseDriver::MySQL->defaultPort());
		$this->assertEquals(5432, DatabaseDriver::PostgreSQL->defaultPort());
		$this->assertEquals(0, DatabaseDriver::SQLite->defaultPort());
		$this->assertEquals(0, DatabaseDriver::SQLite3->defaultPort());
	}

	// --------------------------------------------------------------------

	public function test_supports_transactions()
	{
		$this->assertTrue(DatabaseDriver::MySQL->supportsTransactions());
		$this->assertTrue(DatabaseDriver::PostgreSQL->supportsTransactions());
		$this->assertTrue(DatabaseDriver::SQLite->supportsTransactions());
		$this->assertTrue(DatabaseDriver::SQLite3->supportsTransactions());
	}

	// --------------------------------------------------------------------

	public function test_supports_savepoints()
	{
		$this->assertTrue(DatabaseDriver::MySQL->supportsSavepoints());
		$this->assertTrue(DatabaseDriver::PostgreSQL->supportsSavepoints());
		$this->assertTrue(DatabaseDriver::SQLite->supportsSavepoints());
		$this->assertTrue(DatabaseDriver::SQLite3->supportsSavepoints());
	}

	// --------------------------------------------------------------------

	public function test_supports_upsert()
	{
		$this->assertTrue(DatabaseDriver::MySQL->supportsUpsert());
		$this->assertTrue(DatabaseDriver::PostgreSQL->supportsUpsert());
		$this->assertFalse(DatabaseDriver::SQLite->supportsUpsert());
		$this->assertFalse(DatabaseDriver::SQLite3->supportsUpsert());
	}

	// --------------------------------------------------------------------

	public function test_supports_json_columns()
	{
		$this->assertTrue(DatabaseDriver::MySQL->supportsJsonColumns());
		$this->assertTrue(DatabaseDriver::PostgreSQL->supportsJsonColumns());
		$this->assertFalse(DatabaseDriver::SQLite->supportsJsonColumns());
		$this->assertFalse(DatabaseDriver::SQLite3->supportsJsonColumns());
	}

	// --------------------------------------------------------------------

	public function test_supports_full_text()
	{
		$this->assertTrue(DatabaseDriver::MySQL->supportsFullText());
		$this->assertTrue(DatabaseDriver::PostgreSQL->supportsFullText());
		$this->assertFalse(DatabaseDriver::SQLite->supportsFullText());
		$this->assertFalse(DatabaseDriver::SQLite3->supportsFullText());
	}

	// --------------------------------------------------------------------

	public function test_all_drivers_have_display_name()
	{
		foreach (DatabaseDriver::cases() as $driver)
		{
			$this->assertNotEmpty($driver->displayName());
		}
	}

	// --------------------------------------------------------------------

	public function test_all_drivers_have_positive_port_or_zero()
	{
		foreach (DatabaseDriver::cases() as $driver)
		{
			$this->assertGreaterThanOrEqual(0, $driver->defaultPort());
		}
	}
}
