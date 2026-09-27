<?php
declare(strict_types=1);

use Customigniter\Console\Commands\GeneratorCommand;
use PHPUnit\Framework\TestCase;

class GeneratorCommandTest extends TestCase
{
	private string $dir;

	// --------------------------------------------------------------------

	protected function setUp(): void
	{
		$this->dir = sys_get_temp_dir().DIRECTORY_SEPARATOR.'ci3_gen_'.str_replace('.', '', uniqid('', true));
		mkdir($this->dir, 0777, true);
		FakeGeneratorCommand::$targetDir = $this->dir;
	}

	// --------------------------------------------------------------------

	protected function tearDown(): void
	{
		$this->removeTree($this->dir);
	}

	// --------------------------------------------------------------------

	private function removeTree(string $path): void
	{
		if ( ! is_dir($path))
		{
			return;
		}

		$items = scandir($path);

		if ($items === false)
		{
			return;
		}

		foreach ($items as $item)
		{
			if ($item === '.' OR $item === '..')
			{
				continue;
			}

			$file = $path.DIRECTORY_SEPARATOR.$item;
			is_dir($file) ? $this->removeTree($file) : unlink($file);
		}

		rmdir($path);
	}

	// --------------------------------------------------------------------

	public function test_generates_a_file_for_a_valid_name(): void
	{
		$command = new FakeGeneratorCommand();

		$this->assertSame(0, $command->execute(['my_thing']));

		$path = $this->dir.DIRECTORY_SEPARATOR.'my_thing.php';
		$this->assertFileExists($path);
		$this->assertStringContainsString('my_thing', (string) file_get_contents($path));
	}

	// --------------------------------------------------------------------

	public function test_creates_missing_directories(): void
	{
		$command = new FakeGeneratorCommand();

		$this->assertSame(0, $command->execute(['nested_thing', '--subdir', 'deep/nested']));
		$this->assertFileExists($this->dir.'/deep/nested/nested_thing.php');
	}

	// --------------------------------------------------------------------

	public function test_refuses_invalid_names(): void
	{
		$command = new FakeGeneratorCommand();

		$this->assertSame(1, $command->execute(['bad name!']));
		$this->assertSame(1, $command->execute([]));
	}

	// --------------------------------------------------------------------

	public function test_refuses_to_overwrite_existing_files(): void
	{
		file_put_contents($this->dir.DIRECTORY_SEPARATOR.'my_thing.php', '<?php // existing');
		$command = new FakeGeneratorCommand();

		$this->assertSame(1, $command->execute(['my_thing']));
		$this->assertSame('<?php // existing', file_get_contents($this->dir.DIRECTORY_SEPARATOR.'my_thing.php'));
	}

	// --------------------------------------------------------------------

	public function test_rejects_path_traversal_in_subdir(): void
	{
		$command = new FakeGeneratorCommand();

		$this->assertSame(1, $command->execute(['evil', '--subdir', '../outside']));
		$this->assertFileDoesNotExist($this->dir.DIRECTORY_SEPARATOR.'evil.php');
	}
}

// --------------------------------------------------------------------

/**
 * Test double exposing the generator flow with a temp target directory
 */
final class FakeGeneratorCommand extends GeneratorCommand
{
	public static string $targetDir = '';

	protected function type(): string
	{
		return 'fake';
	}

	protected function baseDirectory(): string
	{
		return 'fakes';
	}

	protected function targetDirectory(string $subdir = ''): string
	{
		return self::$targetDir.($subdir === '' ? '' : '/'.trim($subdir, '/'));
	}

	protected function template(string $name, string $fileName): string
	{
		return "<?php // generated {$name}\n";
	}

	public function getName(): string
	{
		return 'make:fake';
	}

	public function getDescription(): string
	{
		return 'Fake generator for tests';
	}

	public function getUsage(): string
	{
		return 'make:fake <name> [--subdir=<directory>]';
	}
}
