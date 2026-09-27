<?php
declare(strict_types=1);

namespace Customigniter\Console;

use Customigniter\Console\Commands\CliCommand;
use Customigniter\Console\Commands\ClearCacheCommand;
use Customigniter\Console\Commands\MakeControllerCommand;
use Customigniter\Console\Commands\MakeHelperCommand;
use Customigniter\Console\Commands\MakeLibraryCommand;
use Customigniter\Console\Commands\MakeMigrationCommand;
use Customigniter\Console\Commands\MakeModelCommand;
use Customigniter\Console\Commands\MakeSeederCommand;
use Customigniter\Console\Commands\MakeViewCommand;
use Customigniter\Console\Commands\MigrateCommand;
use Customigniter\Console\Commands\MigrateRollbackCommand;
use Customigniter\Console\Commands\MigrateStatusCommand;
use Customigniter\Console\Commands\OptimizeCommand;
use Customigniter\Console\Commands\SeedCommand;
use Customigniter\Console\Commands\ServeCommand;

/**
 * CLI Kernel
 *
 * Routes CLI arguments to registered commands. Similar to an artisan console kernel.
 *
 * @package	Customigniter3
 */
class CliKernel
{
	/**
	 * Registered commands
	 *
	 * @var array<string, CommandInterface>
	 */
	private array $commands = [];

	// --------------------------------------------------------------------

	/**
	 * Constructor — registers built-in commands
	 */
	public function __construct()
	{
		$this->registerDefaults();
	}

	// --------------------------------------------------------------------

	/**
	 * Register the default set of commands
	 *
	 * @return	void
	 */
	private function registerDefaults(): void
	{
		$this->register(new CliCommand());
		$this->register(new ClearCacheCommand());
		$this->register(new ServeCommand());
		$this->register(new OptimizeCommand());

		// Scaffolding
		$this->register(new MakeControllerCommand());
		$this->register(new MakeModelCommand());
		$this->register(new MakeLibraryCommand());
		$this->register(new MakeHelperCommand());
		$this->register(new MakeViewCommand());
		$this->register(new MakeMigrationCommand());
		$this->register(new MakeSeederCommand());

		// Database
		$this->register(new MigrateCommand());
		$this->register(new MigrateRollbackCommand());
		$this->register(new MigrateStatusCommand());
		$this->register(new SeedCommand());
	}

	// --------------------------------------------------------------------

	/**
	 * Register a command
	 *
	 * @param	CommandInterface	$command
	 * @return	static
	 */
	public function register(CommandInterface $command): static
	{
		$this->commands[$command->getName()] = $command;
		$this->syncListCommand();

		return $this;
	}

	// --------------------------------------------------------------------

	/**
	 * Keep the built-in 'list' command in sync with the registry
	 *
	 * @return	void
	 */
	private function syncListCommand(): void
	{
		if (($list = $this->commands['list'] ?? null) instanceof CliCommand)
		{
			$list->setCommands($this->commands);
		}
	}

	// --------------------------------------------------------------------

	/**
	 * Get a registered command by name
	 *
	 * @param	string	$name
	 * @return	CommandInterface|null
	 */
	public function getCommand(string $name): ?CommandInterface
	{
		return $this->commands[$name] ?? null;
	}

	// --------------------------------------------------------------------

	/**
	 * Get all registered commands
	 *
	 * @return	array<string, CommandInterface>
	 */
	public function getCommands(): array
	{
		return $this->commands;
	}

	// --------------------------------------------------------------------

	/**
	 * Run the kernel with CLI arguments
	 *
	 * @param	array<int, string>	$argv	CLI arguments (typically $argv from PHP)
	 * @return	int	Exit code
	 */
	public function run(array $argv): int
	{
		// Remove script name from argv
		$args = array_slice($argv, 1);

		if (empty($args)) {
			return $this->showHelp();
		}

		$commandName = $args[0];
		$commandArgs = array_slice($args, 1);

		// Handle --help flag globally
		if ($commandName === '--help' || $commandName === '-h') {
			return $this->showHelp();
		}

		$command = $this->getCommand($commandName);

		if ($command === null) {
			fwrite(STDERR, "Unknown command: {$commandName}" . PHP_EOL);
			fwrite(STDERR, "Run 'customigniter list' to see available commands." . PHP_EOL);
			return 1;
		}

		// Handle per-command --help
		if (in_array('--help', $commandArgs, true) || in_array('-h', $commandArgs, true)) {
			$this->showCommandHelp($command);
			return 0;
		}

		return $command->execute($commandArgs);
	}

	// --------------------------------------------------------------------

	/**
	 * Show general help listing all commands
	 *
	 * @return	int
	 */
	private function showHelp(): int
	{
		fwrite(STDOUT, PHP_EOL . 'Customigniter 3 - CLI Commands' . PHP_EOL);
		fwrite(STDOUT, str_repeat('-', 50) . PHP_EOL);

		foreach ($this->commands as $name => $command) {
			fwrite(STDOUT, sprintf('  %-30s %s' . PHP_EOL, $name, $command->getDescription()));
		}

		fwrite(STDOUT, PHP_EOL . 'Usage: customigniter <command> [options] [args]' . PHP_EOL);
		fwrite(STDOUT, 'Options: --help  Show command help' . PHP_EOL);

		return 0;
	}

	// --------------------------------------------------------------------

	/**
	 * Show help for a specific command
	 *
	 * @param	CommandInterface	$command
	 * @return	void
	 */
	private function showCommandHelp(CommandInterface $command): void
	{
		fwrite(STDOUT, PHP_EOL . $command->getName() . PHP_EOL);
		fwrite(STDOUT, str_repeat('-', 40) . PHP_EOL);
		fwrite(STDOUT, $command->getDescription() . PHP_EOL);
		fwrite(STDOUT, PHP_EOL . 'Usage: ' . $command->getUsage() . PHP_EOL);
	}
}
