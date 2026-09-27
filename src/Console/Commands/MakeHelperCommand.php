<?php
declare(strict_types=1);

namespace Customigniter\Console\Commands;

/**
 * Make Helper Command
 *
 * Generates {name}_helper.php containing one guarded function.
 *
 * @package	Customigniter3
 */
class MakeHelperCommand extends GeneratorCommand
{
	protected function type(): string
	{
		return 'helper';
	}

	protected function baseDirectory(): string
	{
		return 'helpers';
	}

	public function getName(): string
	{
		return 'make:helper';
	}

	public function getDescription(): string
	{
		return 'Create a new helper file';
	}

	public function getUsage(): string
	{
		return 'make:helper <name>';
	}

	protected function fileName(string $name): string
	{
		return strtolower($name).'_helper.php';
	}

	protected function template(string $name, string $fileName): string
	{
		$function = strtolower($name);
		$label = ucfirst($function);

		$template = <<<'PHP'
<?php
defined('BASEPATH') OR exit('No direct script access allowed');

if ( ! function_exists('{FUNCTION}'))
{
	/**
	 * {LABEL} helper
	 *
	 * @return	void
	 */
	function {FUNCTION}()
	{
	}
}
PHP;

		return strtr($template, ['{FUNCTION}' => $function, '{LABEL}' => $label]);
	}
}
