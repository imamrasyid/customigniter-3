<?php
declare(strict_types=1);

namespace Customigniter\Console;

/**
 * CLI Command Interface
 *
 * Contract for all CLI commands in the Customigniter framework.
 *
 * @package	Customigniter3
 */
interface CommandInterface
{
	/**
	 * Get the command name
	 *
	 * @return	string
	 */
	public function getName(): string;

	/**
	 * Get the command description
	 *
	 * @return	string
	 */
	public function getDescription(): string;

	/**
	 * Get usage information
	 *
	 * @return	string
	 */
	public function getUsage(): string;

	/**
	 * Execute the command
	 *
	 * @param	array<int, string>	$args	Arguments passed after the command name
	 * @return	int	Exit code (0 = success)
	 */
	public function execute(array $args): int;
}
