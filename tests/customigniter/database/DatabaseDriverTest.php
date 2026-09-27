<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use Customigniter\Database\DatabaseDriver;

class DatabaseDriverTest extends TestCase
{
	// --------------------------------------------------------------------

	public function test_from_string_mysqli(): void
	{
		$this->assertEquals(DatabaseDriver::MySQL, DatabaseDriver::tryFrom('mysqli'));
	}

	// --------------------------------------------------------------------

	/**
	 * @dataProvider provide_invalid_drivers
	 */
	public function test_from_string_invalid(string $value): void
	{
		$this->assertNull(DatabaseDriver::tryFrom($value));
	}

	/**
	 * @return array<int, array{string}>
	 */
	public static function provide_invalid_drivers(): array
	{
		return [['invalid'], [''], ['unknown']];
	}

	// --------------------------------------------------------------------

	public function test_display_name(): void
	{
		$this->assertEquals('MySQL', DatabaseDriver::MySQL->displayName());
		$this->assertEquals('PostgreSQL', DatabaseDriver::PostgreSQL->displayName());
		$this->assertEquals('SQLite', DatabaseDriver::SQLite->displayName());
		$this->assertEquals('SQLite3', DatabaseDriver::SQLite3->displayName());
	}

	// --------------------------------------------------------------------

	public function test_default_port(): void
	{
		$this->assertEquals(3306, DatabaseDriver::MySQL->defaultPort());
		$this->assertEquals(5432, DatabaseDriver::PostgreSQL->defaultPort());
		$this->assertEquals(0, DatabaseDriver::SQLite->defaultPort());
		$this->assertEquals(0, DatabaseDriver::SQLite3->defaultPort());
	}

	// --------------------------------------------------------------------

	public function test_supports_transactions(): void
	{
		$this->assertTrue(DatabaseDriver::MySQL->supportsTransactions());
		$this->assertTrue(DatabaseDriver::PostgreSQL->supportsTransactions());
		$this->assertTrue(DatabaseDriver::SQLite->supportsTransactions());
		$this->assertTrue(DatabaseDriver::SQLite3->supportsTransactions());
	}

	// --------------------------------------------------------------------

	public function test_supports_savepoints(): void
	{
		$this->assertTrue(DatabaseDriver::MySQL->supportsSavepoints());
		$this->assertTrue(DatabaseDriver::PostgreSQL->supportsSavepoints());
		$this->assertTrue(DatabaseDriver::SQLite->supportsSavepoints());
		$this->assertTrue(DatabaseDriver::SQLite3->supportsSavepoints());
	}

	// --------------------------------------------------------------------

	public function test_supports_upsert(): void
	{
		$this->assertTrue(DatabaseDriver::MySQL->supportsUpsert());
		$this->assertTrue(DatabaseDriver::PostgreSQL->supportsUpsert());
		$this->assertFalse(DatabaseDriver::SQLite->supportsUpsert());
		$this->assertFalse(DatabaseDriver::SQLite3->supportsUpsert());
	}

	// --------------------------------------------------------------------

	public function test_supports_json_columns(): void
	{
		$this->assertTrue(DatabaseDriver::MySQL->supportsJsonColumns());
		$this->assertTrue(DatabaseDriver::PostgreSQL->supportsJsonColumns());
		$this->assertFalse(DatabaseDriver::SQLite->supportsJsonColumns());
		$this->assertFalse(DatabaseDriver::SQLite3->supportsJsonColumns());
	}

	// --------------------------------------------------------------------

	public function test_supports_full_text(): void
	{
		$this->assertTrue(DatabaseDriver::MySQL->supportsFullText());
		$this->assertTrue(DatabaseDriver::PostgreSQL->supportsFullText());
		$this->assertFalse(DatabaseDriver::SQLite->supportsFullText());
		$this->assertFalse(DatabaseDriver::SQLite3->supportsFullText());
	}

	// --------------------------------------------------------------------

	public function test_all_drivers_have_display_name(): void
	{
		foreach (DatabaseDriver::cases() as $driver)
		{
			$this->assertNotEmpty($driver->displayName());
		}
	}

	// --------------------------------------------------------------------

	public function test_all_drivers_have_positive_port_or_zero(): void
	{
		foreach (DatabaseDriver::cases() as $driver)
		{
			$this->assertGreaterThanOrEqual(0, $driver->defaultPort());
		}
	}
}
