<?php
declare(strict_types=1);

namespace Customigniter\Debug;

use Customigniter\Events\Events;
use Customigniter\Http\Request;

/**
 * Debug Toolbar
 *
 * Collects request, timing and query data at the end of the request
 * and appends an embedded, self-contained panel to the output.
 *
 * Enable it with DEBUG_TOOLBAR=true in the .env file or by setting
 * $config['debug_toolbar'] = TRUE; the listener is attached from
 * CodeIgniter.php while the request boots.
 *
 * @package	Customigniter3
 */
final class Toolbar
{
	/**
	 * Whether the display listener is attached
	 *
	 * @var	bool
	 */
	private static bool $attached = false;

	/**
	 * Whether the panel was already rendered this request
	 *
	 * @var	bool
	 */
	private static bool $rendered = false;

	// --------------------------------------------------------------------

	/**
	 * Should the toolbar be attached for this request?
	 *
	 * @return	bool
	 */
	public static function isEnabled(): bool
	{
		if (env('DEBUG_TOOLBAR', false) === true)
		{
			return true;
		}

		return config_item('debug_toolbar') === true;
	}

	// --------------------------------------------------------------------

	/**
	 * Listen for the display_override hook point
	 *
	 * @return	void
	 */
	public static function attach(): void
	{
		if (self::$attached)
		{
			return;
		}

		self::$attached = true;
		Events::on('display_override', static function (): void {
			Toolbar::displayOverride();
		});
	}

	// --------------------------------------------------------------------

	/**
	 * Append the rendered panel to the outgoing output
	 *
	 * Called through the display_override event, before CI_Output
	 * flushes the body.
	 *
	 * @return	void
	 */
	public static function displayOverride(): void
	{
		if (self::$rendered)
		{
			return;
		}

		$instance = function_exists('get_instance') ? get_instance() : null;

		if ( ! is_object($instance) OR ! isset($instance->output))
		{
			return;
		}

		$output = $instance->output;

		if ( ! is_object($output) OR ! method_exists($output, 'get_output') OR ! method_exists($output, 'set_output'))
		{
			return;
		}

		self::$rendered = true;
		$existing = $output->get_output();
		$existing = is_string($existing) ? $existing : '';
		$output->set_output($existing.self::render(self::collect()));
	}

	// --------------------------------------------------------------------

	/**
	 * Gather toolbar data for the current request
	 *
	 * @param	Request|null	$request	Request to describe; defaults to the globals
	 * @return	array{
	 *		time: array{elapsed: string, memory: string, peak: string},
	 *		request: array{method: string, uri: string, ip: string, agent: string, ajax: bool},
	 *		queries: list<array{query: string, time: float}>,
	 *		query_time: string,
	 *		environment: array{php: string, environment: string, server: string}
	 * }
	 */
	public static function collect(?Request $request = null): array
	{
		$request = $request ?? new Request();
		$time = [
			'elapsed' => '0.0000',
			'memory'  => '0 B',
			'peak'    => self::humanBytes(memory_get_peak_usage(true)),
		];

		if (function_exists('load_class'))
		{
			$benchmark = load_class('Benchmark', 'core');

			if (method_exists($benchmark, 'elapsed_time'))
			{
				$elapsed = $benchmark->elapsed_time();

				if (is_string($elapsed))
				{
					$time['elapsed'] = $elapsed;
				}
			}

			if (method_exists($benchmark, 'memory_usage'))
			{
				$memory = $benchmark->memory_usage();

				if (is_string($memory))
				{
					$time['memory'] = $memory;
				}
			}
		}

		$queries = [];
		$queryTime = 0.0;
		$instance = function_exists('get_instance') ? get_instance() : null;

		if (is_object($instance) AND isset($instance->db))
		{
			$db = $instance->db;
			$times = (is_object($db) AND isset($db->query_times) AND is_array($db->query_times)) ? $db->query_times : [];

			if (is_object($db) AND isset($db->queries) AND is_array($db->queries))
			{
				foreach ($db->queries as $index => $query)
				{
					$duration = (isset($times[$index]) AND is_numeric($times[$index])) ? (float) $times[$index] : 0.0;
					$queries[] = [
						'query' => is_string($query) ? $query : '',
						'time'  => $duration,
					];
					$queryTime += $duration;
				}
			}
		}

		$server = getenv('SERVER_SOFTWARE');

		return [
			'time' => $time,
			'request' => [
				'method' => $request->method(),
				'uri'    => $request->uri(),
				'ip'     => $request->ip(),
				'agent'  => $request->userAgent(),
				'ajax'   => $request->isAjax(),
			],
			'queries'     => $queries,
			'query_time'  => sprintf('%.4f', $queryTime),
			'environment' => [
				'php'         => PHP_VERSION,
				'environment' => defined('ENVIRONMENT') ? ENVIRONMENT : 'development',
				'server'      => is_string($server) ? $server : 'n/a',
			],
		];
	}

	// --------------------------------------------------------------------

	/**
	 * Render the panel HTML for the given data set
	 *
	 * @param	array<string, mixed>	$data
	 * @return	string
	 */
	public static function render(array $data): string
	{
		return ToolbarRenderer::render($data);
	}

	// --------------------------------------------------------------------

	/**
	 * Reset the per-request guards (used by tests)
	 *
	 * @return	void
	 */
	public static function reset(): void
	{
		self::$attached = false;
		self::$rendered = false;
	}

	// --------------------------------------------------------------------

	/**
	 * Human readable byte size
	 *
	 * @param	int		$bytes
	 * @return	string
	 */
	private static function humanBytes(int $bytes): string
	{
		$units = ['B', 'KB', 'MB', 'GB'];
		$index = 0;
		$value = (float) $bytes;

		while ($value >= 1024.0 AND $index < count($units) - 1)
		{
			$value /= 1024.0;
			$index++;
		}

		return sprintf($index === 0 ? '%.0f %s' : '%.1f %s', $value, $units[$index]);
	}
}
