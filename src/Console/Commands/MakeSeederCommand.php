<?php
declare(strict_types=1);

namespace Customigniter\Console\Commands;

/**
 * Make Seeder Command
 *
 * Generates a seeder class inside application/database/seeds.
 *
 * @package	Customigniter3
 */
class MakeSeederCommand extends GeneratorCommand
{
	protected function type(): string
	{
		return 'seeder';
	}

	protected function baseDirectory(): string
	{
		return 'database/seeds';
	}

	public function getName(): string
	{
		return 'make:seeder';
	}

	public function getDescription(): string
	{
		return 'Create a new database seeder class';
	}

	public function getUsage(): string
	{
		return 'make:seeder <name>';
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

use Customigniter\Database\Seeder;

/**
 * {CLASS} Seeder
 */
class {CLASS} extends Seeder
{
	/**
	 * Populate the database
	 */
	public function run(): void
	{
		// $this->db()->insert('table', ['column' => 'value']);
		// $this->call(OtherSeeder::class);
	}
}
PHP;

		return strtr($template, ['{CLASS}' => $class]);
	}
}
