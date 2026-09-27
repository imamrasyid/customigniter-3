<?php
declare(strict_types=1);

use Customigniter\Core\EnvLoader;
use PHPUnit\Framework\TestCase;

class EnvLoaderTest extends TestCase
{
	private string $dir;

	// --------------------------------------------------------------------

	protected function setUp(): void
	{
		EnvLoader::reset();
		$this->dir = sys_get_temp_dir().DIRECTORY_SEPARATOR.'ci3_env_'.str_replace('.', '', uniqid('', true));
		mkdir($this->dir, 0777, true);
	}

	// --------------------------------------------------------------------

	protected function tearDown(): void
	{
		EnvLoader::reset();

		foreach ([
			'CI_TEST_FOO', 'CI_TEST_EXPORTED', 'CI_TEST_DOUBLE', 'CI_TEST_LITERAL',
			'CI_TEST_COMMENT', 'CI_TEST_EMPTY', 'CI_TEST_EXISTING', 'CI_TEST_BOOL_TRUE',
			'CI_TEST_BOOL_FALSE', 'CI_TEST_NULL', 'CI_TEST_INT', 'CI_TEST_FLOAT',
			'CI_TEST_STR', 'CI_TEST_QUOTED_BOOL',
		] as $key)
		{
			putenv($key);
			unset($_ENV[$key]);
		}

		$file = $this->dir.DIRECTORY_SEPARATOR.'.env';

		if (is_file($file))
		{
			unlink($file);
		}

		if (is_dir($this->dir))
		{
			rmdir($this->dir);
		}
	}

	// --------------------------------------------------------------------

	private function writeEnv(string $contents): void
	{
		file_put_contents($this->dir.DIRECTORY_SEPARATOR.'.env', $contents);
	}

	// --------------------------------------------------------------------

	public function test_load_parses_values_and_publishes_them(): void
	{
		$this->writeEnv("CI_TEST_FOO=bar\n");

		$this->assertTrue(EnvLoader::load($this->dir));
		$this->assertSame('bar', EnvLoader::get('CI_TEST_FOO'));
		$this->assertSame('bar', getenv('CI_TEST_FOO'));
		$this->assertSame('bar', $_ENV['CI_TEST_FOO'] ?? null);
	}

	// --------------------------------------------------------------------

	public function test_supports_export_quotes_and_comments(): void
	{
		$this->writeEnv(
			"# comment line\n".
			"export CI_TEST_EXPORTED=exported_val\n".
			"CI_TEST_DOUBLE=\"hello world\"\n".
			"CI_TEST_LITERAL='raw \$value'\n".
			"CI_TEST_COMMENT=value # trailing note\n".
			"CI_TEST_EMPTY=\n"
		);

		EnvLoader::load($this->dir);

		$this->assertSame('exported_val', EnvLoader::get('CI_TEST_EXPORTED'));
		$this->assertSame('hello world', EnvLoader::get('CI_TEST_DOUBLE'));
		$this->assertSame('raw $value', EnvLoader::get('CI_TEST_LITERAL'));
		$this->assertSame('value', EnvLoader::get('CI_TEST_COMMENT'));
		$this->assertSame('', EnvLoader::get('CI_TEST_EMPTY'));
	}

	// --------------------------------------------------------------------

	public function test_existing_environment_variable_wins(): void
	{
		putenv('CI_TEST_EXISTING=real_value');
		$this->writeEnv("CI_TEST_EXISTING=file_value\n");

		EnvLoader::load($this->dir);

		$this->assertSame('real_value', EnvLoader::get('CI_TEST_EXISTING'));
	}

	// --------------------------------------------------------------------

	public function test_missing_file_returns_false_but_marks_loaded(): void
	{
		$this->assertFalse(EnvLoader::load($this->dir));
		$this->assertTrue(EnvLoader::isLoaded());
	}

	// --------------------------------------------------------------------

	public function test_env_helper_casts_unquoted_values(): void
	{
		$this->writeEnv(
			"CI_TEST_BOOL_TRUE=true\n".
			"CI_TEST_BOOL_FALSE=false\n".
			"CI_TEST_NULL=null\n".
			"CI_TEST_INT=42\n".
			"CI_TEST_FLOAT=3.14\n".
			"CI_TEST_STR=hello\n".
			"CI_TEST_QUOTED_BOOL=\"true\"\n"
		);

		EnvLoader::load($this->dir);

		$this->assertTrue(env('CI_TEST_BOOL_TRUE'));
		$this->assertFalse(env('CI_TEST_BOOL_FALSE'));
		$this->assertNull(env('CI_TEST_NULL'));
		$this->assertSame(42, env('CI_TEST_INT'));
		$this->assertSame(3.14, env('CI_TEST_FLOAT'));
		$this->assertSame('hello', env('CI_TEST_STR'));
		$this->assertSame('true', env('CI_TEST_QUOTED_BOOL'));
	}

	// --------------------------------------------------------------------

	public function test_env_helper_returns_default_for_missing_keys(): void
	{
		$this->assertSame('fallback', env('CI_TEST_MISSING_KEY', 'fallback'));
		$this->assertNull(env('CI_TEST_MISSING_KEY'));
	}

	// --------------------------------------------------------------------

	public function test_reset_clears_loaded_state(): void
	{
		$this->writeEnv("CI_TEST_FOO=bar\n");
		EnvLoader::load($this->dir);
		$this->assertSame('bar', EnvLoader::get('CI_TEST_FOO'));

		EnvLoader::reset();

		$this->assertNull(EnvLoader::get('CI_TEST_FOO'));
		$this->assertFalse(EnvLoader::isLoaded());
	}
}
