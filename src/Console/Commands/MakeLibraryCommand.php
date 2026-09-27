<?php
declare(strict_types=1);

namespace Customigniter\Console\Commands;

/**
 * Make Library Command
 *
 * @package	Customigniter3
 */
class MakeLibraryCommand extends GeneratorCommand
{
	protected function type(): string
	{
		return 'library';
	}

	protected function baseDirectory(): string
	{
		return 'libraries';
	}

	public function getName(): string
	{
		return 'make:library';
	}

	public function getDescription(): string
	{
		return 'Create a new library class';
	}

	public function getUsage(): string
	{
		return 'make:library <name> [--subdir=<directory>]';
	}

	protected function fileName(string $name): string
	{
		return $this->studly($name).'.php';
	}

	protected function template(string $name, string $fileName): string
	{
		$class = $this->studly($name);

		$template = <<<'PHP'
<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * {CLASS} Library
 */
class {CLASS}
{
	public function __construct()
	{
	}
}
PHP;

		return strtr($template, ['{CLASS}' => $class]);
	}
}
