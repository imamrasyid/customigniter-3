<?php
declare(strict_types=1);

namespace Customigniter\Testing;

/**
 * Feature Test Case
 *
 * Base class for feature tests: dispatches internal requests against
 * your controllers and returns a TestResponse with assertions.
 *
 * The PHPUnit bootstrap must define BASEPATH/APPPATH (and optionally
 * SYSTEM_PATH and PROJECT_BASE). When show_404()/show_error() still
 * terminate the process in your bootstrap, override them with
 * throwing versions first — this repository's test suite does that
 * in tests/mocks/core/common.php.
 *
 * @package	Customigniter3
 */
abstract class TestCase extends \PHPUnit\Framework\TestCase
{
	/**
	 * Controllers directory; empty = <PROJECT_BASE>application/controllers/
	 *
	 * @var	string
	 */
	protected string $controllerPath = '';

	// --------------------------------------------------------------------

	/**
	 * Dispatch an internal request
	 *
	 * @param	string					$method
	 * @param	string					$uri
	 * @param	array<string, mixed>	$params
	 * @param	array<string, mixed>	$server
	 * @return	TestResponse
	 */
	protected function call(string $method, string $uri, array $params = [], array $server = []): TestResponse
	{
		$path = $this->controllerPath;

		if ($path === '')
		{
			$base = defined('PROJECT_BASE') ? PROJECT_BASE : dirname(__DIR__, 2).DIRECTORY_SEPARATOR;

			if ( ! is_string($base) OR $base === '')
			{
				$base = dirname(__DIR__, 2).DIRECTORY_SEPARATOR;
			}

			$path = $base.'application'.DIRECTORY_SEPARATOR.'controllers'.DIRECTORY_SEPARATOR;
		}

		$dispatcher = new Dispatcher($path);

		try
		{
			return $dispatcher->dispatch($method, $uri, $params, $server);
		}
		finally
		{
			$_GET = [];
			$_POST = [];
			$_REQUEST = [];
		}
	}

	// --------------------------------------------------------------------

	/**
	 * @param	array<string, mixed>	$params
	 * @return	TestResponse
	 */
	protected function get(string $uri, array $params = []): TestResponse
	{
		return $this->call('GET', $uri, $params);
	}

	/**
	 * @param	array<string, mixed>	$params
	 * @return	TestResponse
	 */
	protected function post(string $uri, array $params = []): TestResponse
	{
		return $this->call('POST', $uri, $params);
	}

	/**
	 * @param	array<string, mixed>	$params
	 * @return	TestResponse
	 */
	protected function put(string $uri, array $params = []): TestResponse
	{
		return $this->call('PUT', $uri, $params);
	}

	/**
	 * @param	array<string, mixed>	$params
	 * @return	TestResponse
	 */
	protected function patch(string $uri, array $params = []): TestResponse
	{
		return $this->call('PATCH', $uri, $params);
	}

	/**
	 * @param	array<string, mixed>	$params
	 * @return	TestResponse
	 */
	protected function delete(string $uri, array $params = []): TestResponse
	{
		return $this->call('DELETE', $uri, $params);
	}

	/**
	 * @return	TestResponse
	 */
	protected function head(string $uri): TestResponse
	{
		return $this->call('HEAD', $uri);
	}

	/**
	 * Dispatch with a JSON content type
	 *
	 * The data lands in the POST parameters so CI3-style controllers
	 * can read it through $this->input->post()/input().
	 *
	 * @param	string				$method
	 * @param	string				$uri
	 * @param	mixed				$data
	 * @return	TestResponse
	 */
	protected function json(string $method, string $uri, mixed $data = []): TestResponse
	{
		$params = [];

		if (is_array($data))
		{
			foreach ($data as $key => $value)
			{
				if (is_string($key))
				{
					$params[$key] = $value;
				}
			}
		}

		return $this->call($method, $uri, $params, [
			'CONTENT_TYPE' => 'application/json; charset=UTF-8',
		]);
	}
}
