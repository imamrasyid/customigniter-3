<?php
declare(strict_types=1);

namespace Customigniter\Console\Commands;

/**
 * Make View Command
 *
 * @package	Customigniter3
 */
class MakeViewCommand extends GeneratorCommand
{
	protected function type(): string
	{
		return 'view';
	}

	protected function baseDirectory(): string
	{
		return 'views';
	}

	public function getName(): string
	{
		return 'make:view';
	}

	public function getDescription(): string
	{
		return 'Create a new view file';
	}

	public function getUsage(): string
	{
		return 'make:view <name> [--subdir=<directory>]';
	}

	protected function template(string $name, string $fileName): string
	{
		$title = ucfirst(str_replace('_', ' ', $name));

		return <<<PHP
<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="utf-8">
	<title>{$title}</title>
</head>
<body>
</body>
</html>
PHP;
	}
}
