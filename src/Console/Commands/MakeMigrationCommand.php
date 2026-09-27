<?php
declare(strict_types=1);

namespace Customigniter\Console\Commands;

use Customigniter\Database\Migration\MigrationConfig;

/**
 * Make Migration Command
 *
 * Generates a timestamped migration file implementing
 * MigrationInterface inside the configured migration path.
 *
 * @package	Customigniter3
 */
class MakeMigrationCommand extends GeneratorCommand
{
	protected function type(): string
	{
		return 'migration';
	}

	protected function baseDirectory(): string
	{
		return 'migrations';
	}

	public function getName(): string
	{
		return 'make:migration';
	}

	public function getDescription(): string
	{
		return 'Create a new database migration';
	}

	public function getUsage(): string
	{
		return 'make:migration <name>';
	}

	// --------------------------------------------------------------------

	/**
	 * Read the migration path from application/config/migration.php
	 *
	 * @return	string
	 */
	protected function targetDirectory(string $subdir = ''): string
	{
		$config = MigrationConfig::load(
			APPPATH.'config/migration.php',
			APPPATH.'migrations/',
			'migrations'
		);

		return $config['path'];
	}

	// --------------------------------------------------------------------

	protected function fileName(string $name): string
	{
		return date('YmdHis').'_'.$this->studly($name).'.php';
	}

	// --------------------------------------------------------------------

	protected function template(string $name, string $fileName): string
	{
		$class = $this->studly($name);
		$timestamp = (int) substr($fileName, 0, 14);

		$template = <<<'PHP'
<?php
defined('BASEPATH') OR exit('No direct script access allowed');

use Customigniter\Database\Migration\MigrationInterface;
use Customigniter\Database\Migration\SchemaBuilder;
use Customigniter\Database\Migration\TableBuilder;

/**
 * Migration {TIMESTAMP}: {CLASS}
 */
class {CLASS} implements MigrationInterface
{
	/**
	 * Run the migration
	 */
	public function up(SchemaBuilder $schema): void
	{
		// Example:
		// $schema->create('users', function (TableBuilder $table): void {
		//     $table->id('id');
		//     $table->string('email');
		//     $table->string('password');
		//     $table->timestamp('created_at');
		// });
	}

	/**
	 * Reverse the migration
	 */
	public function down(SchemaBuilder $schema): void
	{
		// $schema->drop('users');
	}

	/**
	 * Get the migration timestamp
	 */
	public function getTimestamp(): int
	{
		return {TIMESTAMP};
	}
}
PHP;

		return strtr($template, ['{CLASS}' => $class, '{TIMESTAMP}' => (string) $timestamp]);
	}
}
