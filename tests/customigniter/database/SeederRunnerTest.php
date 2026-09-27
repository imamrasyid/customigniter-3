<?php
declare(strict_types=1);

use Customigniter\Database\SeederRunner;
use PHPUnit\Framework\TestCase;

class SeederRunnerTest extends TestCase
{
	private string $dir;

	// --------------------------------------------------------------------

	protected function setUp(): void
	{
		$this->dir = sys_get_temp_dir().DIRECTORY_SEPARATOR.'ci3_seed_'.str_replace('.', '', uniqid('', true));
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
	 * discover(), the name guard and the missing-file guard never
	 * touch the connection, so a reflection-built instance is enough.
	 */
	private function runner(): SeederRunner
	{
		$reflection = new ReflectionClass(SeederRunner::class);
		$runner = $reflection->newInstanceWithoutConstructor();
		$reflection->getProperty('seedPath')->setValue(
			$runner,
			rtrim($this->dir, '/\\').DIRECTORY_SEPARATOR
		);

		return $runner;
	}

	// --------------------------------------------------------------------

	public function test_discover_lists_seeder_classes_sorted(): void
	{
		file_put_contents($this->dir.DIRECTORY_SEPARATOR.'ZebraSeeder.php', '<?php');
		file_put_contents($this->dir.DIRECTORY_SEPARATOR.'AlphaSeeder.php', '<?php');
		file_put_contents($this->dir.DIRECTORY_SEPARATOR.'README.txt', 'x');

		$this->assertSame(
			['AlphaSeeder', 'ZebraSeeder'],
			$this->runner()->discover()
		);
	}

	// --------------------------------------------------------------------

	public function test_run_rejects_invalid_seeder_names(): void
	{
		$this->expectException(InvalidArgumentException::class);

		$this->runner()->run('bad name!');
	}

	// --------------------------------------------------------------------

	public function test_run_throws_for_missing_seeder_file(): void
	{
		$this->expectException(RuntimeException::class);
		$this->expectExceptionMessageMatches('/Seeder file not found/');

		$this->runner()->run('MissingSeeder');
	}
}
