<?php
declare(strict_types=1);

use Customigniter\Database\Migration\MigrationInterface;
use Customigniter\Database\Migration\MigrationRunner;
use PHPUnit\Framework\TestCase;

class MigrationRunnerTest extends TestCase
{
	private string $dir;

	// --------------------------------------------------------------------

	protected function setUp(): void
	{
		$this->dir = sys_get_temp_dir().DIRECTORY_SEPARATOR.'ci3_mig_'.str_replace('.', '', uniqid('', true));
		mkdir($this->dir, 0777, true);
	}

	// --------------------------------------------------------------------

	protected function tearDown(): void
	{
		foreach ((glob($this->dir.DIRECTORY_SEPARATOR.'*') ?: []) as $file)
		{
			unlink($file);
		}

		if (is_dir($this->dir))
		{
			rmdir($this->dir);
		}
	}

	// --------------------------------------------------------------------

	/**
	 * Build a runner without a database connection
	 *
	 * discover() and loadMigration() only need the migration path.
	 */
	private function runner(): MigrationRunner
	{
		$reflection = new ReflectionClass(MigrationRunner::class);
		$runner = $reflection->newInstanceWithoutConstructor();
		$reflection->getProperty('migrationPath')->setValue(
			$runner,
			rtrim($this->dir, '/\\').DIRECTORY_SEPARATOR
		);

		return $runner;
	}

	// --------------------------------------------------------------------

	public function test_discover_only_returns_prefixed_migration_files(): void
	{
		file_put_contents($this->dir.DIRECTORY_SEPARATOR.'20260101000000_First.php', '<?php');
		file_put_contents($this->dir.DIRECTORY_SEPARATOR.'002_second.php', '<?php');
		file_put_contents($this->dir.DIRECTORY_SEPARATOR.'README.php', '<?php');
		file_put_contents($this->dir.DIRECTORY_SEPARATOR.'notes.txt', 'x');

		$files = $this->runner()->discover();
		$names = array_map('basename', $files);

		$this->assertSame(['002_second.php', '20260101000000_First.php'], $names);
	}

	// --------------------------------------------------------------------

	public function test_load_migration_derives_class_name_from_prefixed_file(): void
	{
		$file = $this->dir.DIRECTORY_SEPARATOR.'20260101120000_MigrationSampleAlpha.php';
		file_put_contents($file, <<<'PHP'
<?php

use Customigniter\Database\Migration\MigrationInterface;
use Customigniter\Database\Migration\SchemaBuilder;

class MigrationSampleAlpha implements MigrationInterface
{
	public function up(SchemaBuilder $schema): void
	{
	}

	public function down(SchemaBuilder $schema): void
	{
	}

	public function getTimestamp(): int
	{
		return 20260101120000;
	}
}
PHP);

		$method = new ReflectionMethod(MigrationRunner::class, 'loadMigration');
		$migration = $method->invoke($this->runner(), $file);

		if ( ! $migration instanceof MigrationInterface)
		{
			throw new RuntimeException('loadMigration() did not return a MigrationInterface instance');
		}

		$this->assertSame(20260101120000, $migration->getTimestamp());
	}

	// --------------------------------------------------------------------

	public function test_load_migration_throws_when_class_is_missing(): void
	{
		$file = $this->dir.DIRECTORY_SEPARATOR.'20260102120000_MigrationSampleMissing.php';
		file_put_contents($file, "<?php\n// no class here\n");

		$method = new ReflectionMethod(MigrationRunner::class, 'loadMigration');

		$this->expectException(RuntimeException::class);
		$this->expectExceptionMessageMatches("/Migration class 'MigrationSampleMissing' not found/");

		$method->invoke($this->runner(), $file);
	}
}
