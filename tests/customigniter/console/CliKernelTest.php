<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use Customigniter\Console\CliKernel;
use Customigniter\Console\Command;
use Customigniter\Console\CommandInterface;

class CliKernelTest extends TestCase
{
	private CliKernel $kernel;

	protected function setUp(): void
	{
		$this->kernel = new CliKernel();
	}

	// --------------------------------------------------------------------

	public function test_register_returns_fluent(): void
	{
		$cmd = new FakeCommand('test-cmd', 'A test command');
		$result = $this->kernel->register($cmd);
		$this->assertSame($this->kernel, $result);
	}

	// --------------------------------------------------------------------

	public function test_get_commands_returns_registered(): void
	{
		$cmds = $this->kernel->getCommands();
		$this->assertArrayHasKey('list', $cmds);
		$this->assertArrayHasKey('cache:clear', $cmds);
		$this->assertArrayHasKey('serve', $cmds);
	}

	// --------------------------------------------------------------------

	public function test_get_command_by_name(): void
	{
		$cmd = $this->kernel->getCommand('list');
		$this->assertInstanceOf(CommandInterface::class, $cmd);
		$this->assertEquals('list', $cmd->getName());
	}

	// --------------------------------------------------------------------

	public function test_get_command_unknown_returns_null(): void
	{
		$this->assertNull($this->kernel->getCommand('nonexistent'));
	}

	// --------------------------------------------------------------------

	public function test_run_no_args_shows_help(): void
	{
		$exitCode = $this->kernel->run(['customigniter']);
		$this->assertEquals(0, $exitCode);
	}

	// --------------------------------------------------------------------

	public function test_run_unknown_command_returns_1(): void
	{
		$exitCode = $this->kernel->run(['customigniter', 'bogus']);
		$this->assertEquals(1, $exitCode);
	}

	// --------------------------------------------------------------------

	public function test_run_list_command(): void
	{
		$this->kernel->register(new FakeCommand('my-cmd', 'My custom command'));
		$exitCode = $this->kernel->run(['customigniter', 'list']);
		$this->assertEquals(0, $exitCode);
	}

	// --------------------------------------------------------------------

	public function test_run_help_flag(): void
	{
		$exitCode = $this->kernel->run(['customigniter', '--help']);
		$this->assertEquals(0, $exitCode);
	}

	// --------------------------------------------------------------------

	public function test_run_command_help(): void
	{
		$exitCode = $this->kernel->run(['customigniter', 'list', '--help']);
		$this->assertEquals(0, $exitCode);
	}

	// --------------------------------------------------------------------

	public function test_custom_command_is_executed(): void
	{
		$this->kernel->register(new FakeCommand('custom', 'Custom test', 42));
		$exitCode = $this->kernel->run(['customigniter', 'custom']);
		$this->assertEquals(42, $exitCode);
	}

	// --------------------------------------------------------------------

	public function test_command_receives_args(): void
	{
		$cmd = new ArgCaptureCommand();
		$this->kernel->register($cmd);
		$this->kernel->run(['customigniter', 'capture', 'hello', 'world']);

		$this->assertEquals(['hello', 'world'], $cmd->capturedArgs);
	}
}

// --------------------------------------------------------------------
// Test doubles
// --------------------------------------------------------------------

class FakeCommand extends Command
{
	private string $name;
	private string $desc;
	private int $exitCode;

	public function __construct(string $name, string $desc, int $exitCode = 0)
	{
		$this->name = $name;
		$this->desc = $desc;
		$this->exitCode = $exitCode;
	}

	public function getName(): string { return $this->name; }
	public function getDescription(): string { return $this->desc; }
	public function getUsage(): string { return $this->name . ' [args]'; }
	public function execute(array $args): int { return $this->exitCode; }
}

class ArgCaptureCommand extends Command
{
	/**
	 * @var array<int, string>
	 */
	public array $capturedArgs = [];

	public function getName(): string { return 'capture'; }
	public function getDescription(): string { return 'Capture args'; }
	public function getUsage(): string { return 'capture [args]'; }
	public function execute(array $args): int
	{
		$this->capturedArgs = $args;
		return 0;
	}
}
