<?php
declare(strict_types=1);

namespace Customigniter\Console\Commands;

/**
 * Make Controller Command
 *
 * @package	Customigniter3
 */
class MakeControllerCommand extends GeneratorCommand
{
	protected function type(): string
	{
		return 'controller';
	}

	protected function baseDirectory(): string
	{
		return 'controllers';
	}

	public function getName(): string
	{
		return 'make:controller';
	}

	public function getDescription(): string
	{
		return 'Create a new controller class';
	}

	public function getUsage(): string
	{
		return 'make:controller <name> [--subdir=<directory>]';
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
 * {CLASS} Controller
 */
class {CLASS} extends CI_Controller
{
	public function __construct()
	{
		parent::__construct();
	}

	/**
	 * Default action
	 *
	 * @return	void
	 */
	public function index()
	{
	}
}
PHP;

		return strtr($template, ['{CLASS}' => $class]);
	}
}
