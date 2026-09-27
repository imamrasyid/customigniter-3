<?php
declare(strict_types=1);

namespace Customigniter\Console\Commands;

use Customigniter\Console\Command;

/**
 * Development Server Command
 *
 * Starts PHP's built-in development server with the project's routing.
 *
 * @package	Customigniter3
 */
class ServeCommand extends Command
{
	public function getName(): string
	{
		return 'serve';
	}

	public function getDescription(): string
	{
		return 'Start the development server';
	}

	public function getUsage(): string
	{
		return 'serve [--host=<host>] [--port=<port>]';
	}

	public function execute(array $args): int
	{
		$parsed = $this->parseOptions($args);
		$host = $this->getOption($parsed, 'host', 'localhost');
		$port = $this->getOption($parsed, 'port', '8080');

		$address = "{$host}:{$port}";

		$this->header('Customigniter 3 Development Server');
		$this->info("Listening on http://{$address}");
		$this->info('Press Ctrl+C to stop.');
		$this->info('');

		// Use PHP's built-in server
		$docRoot = defined('BASEPATH') ? dirname(BASEPATH) : getcwd();
		if ($docRoot === false) {
			$docRoot = getcwd();
		}

		// Keep the generated router outside the project so it can never
		// collide with the "customigniter" bin file in the project root.
		$routerDir = rtrim(sys_get_temp_dir(), DIRECTORY_SEPARATOR)
			.DIRECTORY_SEPARATOR.'customigniter-serve';
		$router = $routerDir.DIRECTORY_SEPARATOR.'router.php';

		// Create a simple router if it doesn't exist
		if ( ! is_dir($routerDir) && ! mkdir($routerDir, 0755, true) && ! is_dir($routerDir)) {
			$this->error("Cannot create router directory: {$routerDir}");
			return 1;
		}

		// Always regenerate: the document root (and router logic) may
		// have changed since the last run.
		$this->createRouter($router, $docRoot);

		$cmd = sprintf(
			'%s -S %s -t %s %s',
			escapeshellarg(PHP_BINARY),
			escapeshellarg($address),
			escapeshellarg($docRoot),
			escapeshellarg($router)
		);

		passthru($cmd, $exitCode);

		return $exitCode;
	}

	private function createRouter(string $path, string $docRoot): void
	{
		// The router lives in a temp directory, so the document root must
		// be baked in as an absolute path instead of dirname(__DIR__).
		$content = str_replace('__DOCROOT__', var_export($docRoot, true), <<<'PHP'
<?php
/**
 * Customigniter 3 Development Router
 *
 * Routes all requests through index.php for the front controller pattern.
 */
$uri = urldecode((string) parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));
$root = __DOCROOT__;

// index.php resolves relative paths ("system") against the CWD, which
// may differ from the document root when php -S is launched elsewhere.
chdir($root);

// Serve existing static files directly (guard against path traversal)
$candidate = realpath($root . DIRECTORY_SEPARATOR . ltrim($uri, '/'));
if ($uri !== '/' && $candidate !== false
	&& strncmp($candidate, $root . DIRECTORY_SEPARATOR, strlen($root . DIRECTORY_SEPARATOR)) === 0
	&& is_file($candidate)) {
	return false;
}

// Route everything else through index.php
require $root . DIRECTORY_SEPARATOR . 'index.php';
PHP);

		if (file_put_contents($path, $content) === false) {
			throw new \RuntimeException("Cannot write router file: {$path}");
		}
	}
}
