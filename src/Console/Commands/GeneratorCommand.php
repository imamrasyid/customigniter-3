<?php
declare(strict_types=1);

namespace Customigniter\Console\Commands;

use Customigniter\Console\Command;

/**
 * Generator Command Base
 *
 * Shared scaffolding flow for all make:* commands: validate the
 * requested name, resolve the target directory (with optional
 * --subdir), refuse to overwrite, and render the stub template.
 *
 * @package	Customigniter3
 */
abstract class GeneratorCommand extends Command
{
	/**
	 * Human-readable artifact type, e.g. 'controller'
	 *
	 * @return	string
	 */
	abstract protected function type(): string;

	/**
	 * Base directory relative to APPPATH, e.g. 'controllers'
	 *
	 * @return	string
	 */
	abstract protected function baseDirectory(): string;

	/**
	 * Render the stub contents
	 *
	 * @param	string	$name	Validated name as requested by the user
	 * @param	string	$fileName	Computed file name (without directory)
	 * @return	string
	 */
	abstract protected function template(string $name, string $fileName): string;

	// --------------------------------------------------------------------

	/**
	 * Computed file name for a validated name
	 *
	 * @param	string	$name
	 * @return	string
	 */
	protected function fileName(string $name): string
	{
		return $name.'.php';
	}

	// --------------------------------------------------------------------

	/**
	 * Studly-case a name: blog_posts -> BlogPosts
	 *
	 * @param	string	$name
	 * @return	string
	 */
	protected function studly(string $name): string
	{
		$parts = preg_split('/[_\-\s]+/', $name) ?: [$name];
		$parts = array_filter($parts, static fn (string $part): bool => $part !== '');

		return implode('', array_map(
			static fn (string $part): string => ucfirst($part),
			array_values($parts)
		));
	}

	// --------------------------------------------------------------------

	/**
	 * Target directory for generation (overridable in tests)
	 *
	 * @param	string	$subdir	Optional sub-directory below the base directory
	 * @return	string
	 */
	protected function targetDirectory(string $subdir = ''): string
	{
		return APPPATH.$this->baseDirectory().($subdir === '' ? '' : '/'.trim($subdir, '/'));
	}

	// --------------------------------------------------------------------

	/**
	 * Execute the generator
	 *
	 * @param	array<int, string>	$args
	 * @return	int
	 */
	public function execute(array $args): int
	{
		$parsed = $this->parseOptions($args);
		$name = $parsed['args'][0] ?? '';

		if ($name === '' OR preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/', $name) !== 1) {
			$this->error('Usage: '.$this->getUsage());
			return 1;
		}

		$subdir = $this->getOption($parsed, 'subdir', '');

		if ($subdir !== '' AND (preg_match('#^[A-Za-z0-9_/-]+$#', $subdir) !== 1 OR str_contains($subdir, '..'))) {
			$this->error("Invalid --subdir value: '{$subdir}'");
			return 1;
		}

		$fileName = $this->fileName($name);
		$directory = $this->targetDirectory($subdir);
		$path = $directory.DIRECTORY_SEPARATOR.$fileName;

		if (is_file($path)) {
			$this->error("File already exists: {$path}");
			return 1;
		}

		if ( ! is_dir($directory) AND ! mkdir($directory, 0755, true) AND ! is_dir($directory)) {
			$this->error("Cannot create directory: {$directory}");
			return 1;
		}

		if (file_put_contents($path, $this->template($name, $fileName)) === false) {
			$this->error("Cannot write file: {$path}");
			return 1;
		}

		$this->success(ucfirst($this->type())." created: {$path}");

		return 0;
	}
}
