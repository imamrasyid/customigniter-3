<?php
declare(strict_types=1);

use Customigniter\Debug\Toolbar;
use Customigniter\Events\Events;

class ToolbarTest extends CI_TestCase
{
	// --------------------------------------------------------------------

	public function set_up(): void
	{
		$this->ci_vfs_clone('system/core/Benchmark.php');
	}

	// --------------------------------------------------------------------

	public function tear_down(): void
	{
		Toolbar::reset();
		Events::reset();
		putenv('DEBUG_TOOLBAR');
		unset($_ENV['DEBUG_TOOLBAR']);
	}

	// --------------------------------------------------------------------

	public function test_disabled_by_default(): void
	{
		$this->assertFalse(Toolbar::isEnabled());
	}

	// --------------------------------------------------------------------

	public function test_enabled_via_environment(): void
	{
		putenv('DEBUG_TOOLBAR=true');
		$this->assertTrue(Toolbar::isEnabled());

		putenv('DEBUG_TOOLBAR=false');
		$this->assertFalse(Toolbar::isEnabled());
	}

	// --------------------------------------------------------------------

	public function test_collect_returns_expected_shape(): void
	{
		$data = Toolbar::collect(new Customigniter\Http\Request([], [], [
			'REQUEST_METHOD' => 'GET',
			'REQUEST_URI'    => '/testing',
		]));

		$this->assertArrayHasKey('time', $data);
		$this->assertArrayHasKey('request', $data);
		$this->assertArrayHasKey('queries', $data);
		$this->assertArrayHasKey('query_time', $data);
		$this->assertArrayHasKey('environment', $data);
		$this->assertCount(0, $data['queries']);
		$this->assertSame('GET', $data['request']['method']);
		$this->assertArrayHasKey('elapsed', $data['time']);
	}

	// --------------------------------------------------------------------

	public function test_render_contains_panel_and_escapes_output(): void
	{
		$html = Toolbar::render([
			'time' => ['elapsed' => '0.0123', 'memory' => '1 MB', 'peak' => '2 MB'],
			'request' => ['method' => 'GET', 'uri' => '/<img src=x onerror=alert(1)>', 'ip' => '127.0.0.1', 'agent' => 'phpunit', 'ajax' => false],
			'queries' => [['query' => 'SELECT * FROM <b>users</b>', 'time' => 0.0042]],
			'query_time' => '0.0042',
			'environment' => ['php' => '8.4.0', 'environment' => 'testing', 'server' => 'n/a'],
		]);

		$this->assertStringContainsString('id="ci-toolbar"', $html);
		$this->assertStringContainsString('ci-toolbar-toggle', $html);
		$this->assertStringContainsString('SELECT * FROM &lt;b&gt;users&lt;/b&gt;', $html);
		$this->assertStringNotContainsString('<img src=x', $html);
		$this->assertStringContainsString('&lt;img src=x', $html);
	}

	// --------------------------------------------------------------------

	public function test_render_shows_empty_state_without_queries(): void
	{
		$html = Toolbar::render(['queries' => []]);

		$this->assertStringContainsString('No database queries recorded.', $html);
	}

	// --------------------------------------------------------------------

	public function test_display_override_appends_panel_to_output(): void
	{
		$fakeOutput = new class {
			public string $out = '<html>page</html>';

			public function get_output(): string
			{
				return $this->out;
			}

			public function set_output(string $output): void
			{
				$this->out = $output;
			}
		};

		$this->ci_instance_var('output', $fakeOutput);

		Toolbar::reset();
		Toolbar::attach();
		Events::trigger('display_override', ['hook' => 'display_override']);

		$this->assertStringStartsWith('<html>page</html>', $fakeOutput->out);
		$this->assertStringContainsString('id="ci-toolbar"', $fakeOutput->out);
	}

	// --------------------------------------------------------------------

	public function test_attach_is_idempotent(): void
	{
		Toolbar::reset();
		Toolbar::attach();
		Toolbar::attach();

		$this->assertCount(1, Events::dispatcher()->listeners('display_override'));
	}

	// --------------------------------------------------------------------

	public function test_display_override_renders_only_once(): void
	{
		$fakeOutput = new class {
			public string $out = 'page';

			public function get_output(): string
			{
				return $this->out;
			}

			public function set_output(string $output): void
			{
				$this->out = $output;
			}
		};

		$this->ci_instance_var('output', $fakeOutput);

		Toolbar::reset();
		Toolbar::attach();
		Events::trigger('display_override', ['hook' => 'display_override']);
		Events::trigger('display_override', ['hook' => 'display_override']);

		$this->assertSame(1, substr_count($fakeOutput->out, 'id="ci-toolbar"'));
	}
}
