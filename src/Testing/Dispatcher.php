<?php
declare(strict_types=1);

namespace Customigniter\Testing;

use Customigniter\Http\Response;

/**
 * Internal Request Dispatcher
 *
 * Boots the framework's core classes (once per process), resolves a
 * URI against the controller directory, invokes the action and
 * captures everything it produced as a TestResponse.
 *
 * @package	Customigniter3
 */
final class Dispatcher
{
	/**
	 * Whether the framework classes were booted
	 *
	 * @var	bool
	 */
	private static bool $booted = false;

	/**
	 * Absolute path to the controllers directory (with trailing slash)
	 *
	 * @var	string
	 */
	private string $controllerPath;

	// --------------------------------------------------------------------

	/**
	 * @param	string	$controllerPath
	 */
	public function __construct(string $controllerPath)
	{
		$this->controllerPath = rtrim($controllerPath, '/\\').DIRECTORY_SEPARATOR;
	}

	// --------------------------------------------------------------------

	/**
	 * Dispatch an internal request
	 *
	 * @param	string					$method		HTTP method
	 * @param	string					$uri		Request URI (path only)
	 * @param	array<string, mixed>	$params		GET/POST parameters
	 * @param	array<string, mixed>	$server		Extra $_SERVER values
	 * @return	TestResponse
	 */
	public function dispatch(string $method, string $uri, array $params = [], array $server = []): TestResponse
	{
		$this->boot();

		[
			'file'   => $file,
			'class'  => $class,
			'action' => $action,
			'args'   => $args,
		] = $this->resolve($uri);

		$method = strtoupper($method);
		$isRead = in_array($method, ['GET', 'HEAD'], true);

		$original = [$_GET, $_POST, $_REQUEST, $_SERVER];

		$_GET = $isRead ? $params : [];
		$_POST = $isRead ? [] : $params;
		$_REQUEST = array_merge($_GET, $_POST);
		$_SERVER = array_merge($_SERVER, [
			'REQUEST_METHOD' => $method,
			'REQUEST_URI'    => $uri,
			'HTTP_ACCEPT'    => 'text/html,application/xhtml+xml,application/json;q=0.9,*/*;q=0.8',
		], $server);

		try
		{
			if ( ! class_exists($class, false))
			{
				require_once $file;
			}

			if ( ! class_exists($class, false))
			{
				throw new \RuntimeException('Controller class "'.$class.'" was not declared by "'.$file.'".');
			}

			$result = null;
			ob_start();

			try
			{
				$controller = new $class();
				$GLOBALS['CI'] = $controller;

				if (method_exists($controller, '_remap'))
				{
					$result = $controller->_remap($action, ...$args);
				}
				elseif ( ! is_callable([$controller, $action]))
				{
					throw new \RuntimeException('Controller method "'.$action.'" was not found on "'.$class.'".');
				}
				else
				{
					$result = $controller->$action(...$args);
				}
			}
			finally
			{
				$body = (string) ob_get_clean();
			}

			$status = http_response_code();
			$headers = [];

			if ($result instanceof Response)
			{
				$body .= $result->getBody();
				$status = $result->getStatusCode();
				$headers = $result->getHeaders();
			}
			elseif (is_string($result))
			{
				$body .= $result;
			}

			// CLI cannot change http_response_code() once PHPUnit has
			// written output, so a bare int() read is the best signal.
			return new TestResponse($body, is_int($status) ? $status : 200, $headers);
		}
		finally
		{
			$_GET = $original[0];
			$_POST = $original[1];
			$_REQUEST = $original[2];
			$_SERVER = $original[3];
		}
	}

	// --------------------------------------------------------------------

	/**
	 * Boot the framework pieces a controller needs
	 *
	 * @return	void
	 */
	private function boot(): void
	{
		if (self::$booted)
		{
			return;
		}

		if ( ! defined('BASEPATH'))
		{
			throw new \RuntimeException('Customigniter\Testing requires BASEPATH to be defined by the PHPUnit bootstrap.');
		}

		$systemPath = defined('SYSTEM_PATH') ? SYSTEM_PATH : BASEPATH;

		if ( ! is_string($systemPath) OR $systemPath === '')
		{
			throw new \RuntimeException('Unable to determine the CodeIgniter system path.');
		}

		if ( ! function_exists('load_class'))
		{
			require_once $systemPath.'core/Common.php';
		}

		if ( ! class_exists('CI_Controller', false))
		{
			require_once $systemPath.'core/Controller.php';
		}

		if ( ! function_exists('get_instance'))
		{
			require_once dirname(__DIR__).DIRECTORY_SEPARATOR.'Testing'.DIRECTORY_SEPARATOR.'functions.php';
		}

		// Core classes the dispatcher itself relies on; the controller
		// constructor resolves the Loader through load_class() as well.
		$config = load_class('Config', 'core');

		if ( ! $config instanceof \CI_Config)
		{
			throw new \RuntimeException('Unable to load the Config core class.');
		}

		$charset = $config->item('charset');
		$charset = (is_string($charset) AND $charset !== '') ? $charset : 'UTF-8';

		load_class('Output', 'core');
		$security = load_class('Security', 'core', $charset);

		if ( ! $security instanceof \CI_Security)
		{
			throw new \RuntimeException('Unable to load the Security core class.');
		}

		load_class('Input', 'core', $security);

		self::$booted = true;
	}

	// --------------------------------------------------------------------

	/**
	 * Resolve a URI against the controller directory
	 *
	 * Mirrors CI3 auto-routing: the longest existing file prefix wins,
	 * the next segment becomes the method and the rest are arguments.
	 *
	 * @param	string	$uri
	 * @return	array{file: string, class: string, action: string, args: list<string>}
	 */
	private function resolve(string $uri): array
	{
		$path = $uri;
		$queryPos = strpos($path, '?');

		if ($queryPos !== false)
		{
			$path = substr($path, 0, $queryPos);
		}

		$segments = [];

		foreach (explode('/', $path) as $segment)
		{
			if ($segment === '')
			{
				continue;
			}

			if ($segment === '.' OR $segment === '..' OR strpbrk($segment, "\0\\") !== false)
			{
				throw new \RuntimeException('Invalid URI segment: '.$segment);
			}

			$segments[] = $segment;
		}

		if ($segments === [])
		{
			$segments = ['welcome'];
		}

		$total = count($segments);

		for ($length = $total; $length >= 1; $length--)
		{
			$prefix = array_slice($segments, 0, $length);
			$rest = array_slice($segments, $length);

			foreach ($this->fileVariants($prefix) as $relative)
			{
				$file = $this->controllerPath.str_replace('/', DIRECTORY_SEPARATOR, $relative).'.php';

				if ( ! is_file($file))
				{
					continue;
				}

				$action = 'index';
				$args = [];

				if ($rest !== [])
				{
					$action = (string) array_shift($rest);

					if (preg_match('/^[A-Za-z0-9_]+$/', $action) !== 1)
					{
						throw new \RuntimeException('Invalid controller method segment: '.$action);
					}

					$args = $rest;
				}

				return [
					'file'   => $file,
					'class'  => ucfirst(str_replace('-', '_', basename($relative))),
					'action' => $action,
					'args'   => $args,
				];
			}
		}

		throw new \RuntimeException('No controller found for URI: '.$uri);
	}

	// --------------------------------------------------------------------

	/**
	 * Case variants to try for a controller path
	 *
	 * CI3 stores controllers as ucfirst() of the URI segment
	 * (welcome -> Welcome.php), but lowercase files work too.
	 *
	 * @param	list<string>	$segments
	 * @return	list<string>
	 */
	private function fileVariants(array $segments): array
	{
		$index = count($segments) - 1;
		$variants = [];

		$asIs = implode('/', $segments);
		$variants[] = $asIs;

		$upperLast = $segments;
		$upperLast[$index] = ucfirst($segments[$index]);
		$upperLastPath = implode('/', $upperLast);

		if ($upperLastPath !== $asIs)
		{
			$variants[] = $upperLastPath;
		}

		return $variants;
	}
}
