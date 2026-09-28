<?php
declare(strict_types=1);

use Customigniter\Testing\Dispatcher;
use Customigniter\Testing\TestCase as FeatureTestCase;
use Customigniter\Testing\TestResponse;

class DispatchTest extends CI_TestCase
{
	private Dispatcher $dispatcher;

	// --------------------------------------------------------------------

	public function set_up(): void
	{
		foreach (['Config', 'Output', 'Input', 'Security', 'Loader'] as $core)
		{
			$this->ci_vfs_clone('system/core/'.$core.'.php');
		}

		$this->dispatcher = new Dispatcher(__DIR__.DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR.'support'.DIRECTORY_SEPARATOR.'controllers');
	}

	// --------------------------------------------------------------------

	/**
	 * @param	array<string, mixed>	$params
	 */
	private function dispatch(string $method, string $uri, array $params = []): TestResponse
	{
		return $this->dispatcher->dispatch($method, $uri, $params);
	}

	// --------------------------------------------------------------------

	public function test_get_dispatches_controller_action(): void
	{
		$this->dispatch('GET', '/welcome')
			->assertOk()
			->assertSee('Welcome to Customigniter!');
	}

	// --------------------------------------------------------------------

	public function test_returned_string_is_captured(): void
	{
		$this->dispatch('GET', '/welcome/greet/Alice')
			->assertOk()
			->assertSee('Hello Alice');
	}

	// --------------------------------------------------------------------

	public function test_echo_and_return_are_combined(): void
	{
		$this->dispatch('GET', '/welcome/leak')
			->assertSee('beforeafter');
	}

	// --------------------------------------------------------------------

	public function test_subdirectory_controller_resolves(): void
	{
		$this->dispatch('GET', '/admin/dashboard')
			->assertOk()
			->assertSee('admin dashboard');

		$this->dispatch('GET', '/admin/dashboard/stats')
			->assertSee('stats');
	}

	// --------------------------------------------------------------------

	public function test_status_code_is_captured(): void
	{
		$this->dispatch('GET', '/welcome/missing')
			->assertNotFound()
			->assertSee('not found');
	}

	// --------------------------------------------------------------------

	public function test_response_object_is_unwrapped(): void
	{
		$response = $this->dispatch('GET', '/welcome/jsonResponse');

		$response->assertOk()
			->assertJson()
			->assertJsonPath('ok', true)
			->assertJsonPath('name', 'ci')
			->assertHeader('Content-Type', 'application/json; charset=UTF-8');
	}

	// --------------------------------------------------------------------

	public function test_redirect_response_is_unwrapped(): void
	{
		$this->dispatch('GET', '/welcome/go')
			->assertRedirect('/login');
	}

	// --------------------------------------------------------------------

	public function test_post_parameters_reach_the_controller(): void
	{
		$this->dispatch('POST', '/welcome/postEcho', ['name' => 'Bob'])
			->assertSee('got:Bob');
	}

	// --------------------------------------------------------------------

	public function test_remap_is_honoured(): void
	{
		$this->dispatch('GET', '/remap/whatever/1/2')
			->assertSee('remap:whatever:1,2');
	}

	// --------------------------------------------------------------------

	public function test_query_string_is_ignored_for_routing(): void
	{
		$this->dispatch('GET', '/welcome/greet/Alice?foo=bar')
			->assertSee('Hello Alice');
	}

	// --------------------------------------------------------------------

	public function test_unknown_controller_throws(): void
	{
		$this->expectException(RuntimeException::class);
		$this->expectExceptionMessageMatches('/No controller found/');

		$this->dispatch('GET', '/does/not/exist');
	}

	// --------------------------------------------------------------------

	public function test_unknown_method_throws(): void
	{
		$this->expectException(RuntimeException::class);
		$this->expectExceptionMessageMatches('/was not found/');

		$this->dispatch('GET', '/welcome/noSuchMethod');
	}

	// --------------------------------------------------------------------

	public function test_show_404_throws_in_test_environment(): void
	{
		$this->expectException(RuntimeException::class);
		$this->expectExceptionMessage('CI Error: 404');

		$this->dispatch('GET', '/welcome/boom');
	}

	// --------------------------------------------------------------------

	public function test_path_traversal_is_rejected(): void
	{
		$this->expectException(RuntimeException::class);
		$this->expectExceptionMessageMatches('/Invalid URI segment/');

		$this->dispatch('GET', '/../secret');
	}
}

// --------------------------------------------------------------------

/**
 * Concrete shim so the public Testing\TestCase API can be probed
 * from inside a CI_TestCase-managed environment
 */
final class FeatureApiShim extends FeatureTestCase
{
	protected string $controllerPath = __DIR__.DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR.'support'.DIRECTORY_SEPARATOR.'controllers';

	public function probe(string $uri): TestResponse
	{
		return $this->get($uri);
	}

	public function probeJson(string $uri): TestResponse
	{
		return $this->json('GET', $uri);
	}
}

// --------------------------------------------------------------------

class TestCaseApiTest extends CI_TestCase
{
	public function set_up(): void
	{
		foreach (['Config', 'Output', 'Input', 'Security', 'Loader'] as $core)
		{
			$this->ci_vfs_clone('system/core/'.$core.'.php');
		}
	}

	// --------------------------------------------------------------------

	public function test_testcase_get_shortcut_works(): void
	{
		$shim = new FeatureApiShim();

		$shim->probe('/welcome')
			->assertOk()
			->assertSee('Welcome to Customigniter!');
	}

	// --------------------------------------------------------------------

	public function test_testcase_json_shortcut_works(): void
	{
		$shim = new FeatureApiShim();

		$shim->probeJson('/welcome/jsonResponse')
			->assertOk()
			->assertJsonPath('ok', true);
	}
}
