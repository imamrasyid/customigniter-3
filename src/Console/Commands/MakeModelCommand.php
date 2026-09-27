<?php
declare(strict_types=1);

namespace Customigniter\Console\Commands;

/**
 * Make Model Command
 *
 * @package	Customigniter3
 */
class MakeModelCommand extends GeneratorCommand
{
	protected function type(): string
	{
		return 'model';
	}

	protected function baseDirectory(): string
	{
		return 'models';
	}

	public function getName(): string
	{
		return 'make:model';
	}

	public function getDescription(): string
	{
		return 'Create a new model class';
	}

	public function getUsage(): string
	{
		return 'make:model <name> [--subdir=<directory>]';
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
 * {CLASS} Model
 */
class {CLASS} extends CI_Model
{
	public function __construct()
	{
		parent::__construct();
	}
}
PHP;

		return strtr($template, ['{CLASS}' => $class]);
	}
}
