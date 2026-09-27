<?php
declare(strict_types=1);

namespace Customigniter\Console\Commands;

use Customigniter\Console\Command;
use Customigniter\Console\CommandInterface;

/**
 * List all available commands
 *
 * @package	Customigniter3
 */
class CliCommand extends Command
{
	/**
	 * @var array<string, CommandInterface>
	 */
	private array $commands = [];

	public function getName(): string
	{
		return 'list';
	}

	public function getDescription(): string
	{
		return 'List all available commands';
	}

	public function getUsage(): string
	{
		return 'list';
	}

	/**
	 * Set the available commands for listing
	 *
	 * Called by CliKernel whenever the command registry changes.
	 *
	 * @param	array<string, CommandInterface>	$commands
	 * @return	void
	 */
	public function setCommands(array $commands): void
	{
		$this->commands = $commands;
	}

	public function execute(array $args): int
	{
		$this->header('Customigniter 3 - Available Commands');

		foreach ($this->commands as $name => $cmd) {
			$this->info(sprintf('  %-30s %s', $name, $cmd->getDescription()));
		}

		$this->info('');
		$this->info('Usage: customigniter <command> [options] [args]');

		return 0;
	}
}
