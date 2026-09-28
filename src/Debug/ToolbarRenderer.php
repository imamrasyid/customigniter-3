<?php
declare(strict_types=1);

namespace Customigniter\Debug;

/**
 * Debug Toolbar Renderer
 *
 * Builds the self-contained HTML/CSS/JS panel for the debug toolbar.
 * Every dynamic value is escaped before it reaches the markup.
 *
 * @package	Customigniter3
 */
final class ToolbarRenderer
{
	// --------------------------------------------------------------------

	/**
	 * Render the toolbar panel
	 *
	 * @param	array<array-key, mixed>		$data
	 * @return	string
	 */
	public static function render(array $data): string
	{
		$time = (isset($data['time']) AND is_array($data['time'])) ? $data['time'] : [];
		$request = (isset($data['request']) AND is_array($data['request'])) ? $data['request'] : [];
		$environment = (isset($data['environment']) AND is_array($data['environment'])) ? $data['environment'] : [];
		$queries = (isset($data['queries']) AND is_array($data['queries'])) ? $data['queries'] : [];
		$queryTime = (isset($data['query_time']) AND is_string($data['query_time'])) ? $data['query_time'] : '0.0000';

		$elapsed = self::value($time, 'elapsed', '0.0000');
		$memory = self::value($time, 'memory', '0 B');
		$peak = self::value($time, 'peak', '0 B');
		$queryCount = count($queries);

		$summary = sprintf('%s s · %s peak · %d %s · %s s',
			self::e($elapsed),
			self::e($peak),
			$queryCount,
			$queryCount === 1 ? 'query' : 'queries',
			self::e($queryTime)
		);

		return self::style().self::markup($summary, $request, $time, $queries, $queryTime, $environment, $memory).self::script();
	}

	// --------------------------------------------------------------------

	/**
	 * Panel markup
	 *
	 * @param	string						$summary
	 * @param	array<array-key, mixed>		$request
	 * @param	array<array-key, mixed>		$time
	 * @param	mixed						$queries
	 * @param	string						$queryTime
	 * @param	array<array-key, mixed>		$environment
	 * @param	string						$memory
	 * @return	string
	 */
	private static function markup(string $summary, array $request, array $time, $queries, string $queryTime, array $environment, string $memory): string
	{
		$databaseRows = '';

		if (is_array($queries))
		{
			foreach ($queries as $query)
			{
				if ( ! is_array($query))
				{
					continue;
				}

				$sql = self::value($query, 'query', '');
				$duration = (isset($query['time']) AND is_numeric($query['time'])) ? (float) $query['time'] : 0.0;
				$databaseRows .= '<tr><td class="ci-q">'.self::e($sql).'</td><td class="ci-num">'
					.self::e(sprintf('%.4f', $duration)).' s</td></tr>';
			}
		}

		if ($databaseRows === '')
		{
			$databaseRows = '<tr><td colspan="2" class="ci-empty">No database queries recorded.</td></tr>';
		}

		$timingRows = '<tr><td>Elapsed</td><td class="ci-num">'.self::e(self::value($time, 'elapsed', '0.0000')).' s</td></tr>'
			.'<tr><td>Memory</td><td class="ci-num">'.self::e($memory).'</td></tr>'
			.'<tr><td>Memory peak</td><td class="ci-num">'.self::e(self::value($time, 'peak', '0 B')).'</td></tr>'
			.'<tr><td>Query time</td><td class="ci-num">'.self::e($queryTime).' s</td></tr>';

		$requestRows = '<tr><td>Method</td><td>'.self::e(self::value($request, 'method', 'GET')).'</td></tr>'
			.'<tr><td>URI</td><td>'.self::e(self::value($request, 'uri', '/')).'</td></tr>'
			.'<tr><td>IP</td><td>'.self::e(self::value($request, 'ip', '-')).'</td></tr>'
			.'<tr><td>AJAX</td><td>'.self::e((bool) ($request['ajax'] ?? false) ? 'yes' : 'no').'</td></tr>'
			.'<tr><td>User agent</td><td>'.self::e(self::value($request, 'agent', '-')).'</td></tr>';

		$environmentRows = '<tr><td>PHP</td><td>'.self::e(self::value($environment, 'php', '-')).'</td></tr>'
			.'<tr><td>Environment</td><td>'.self::e(self::value($environment, 'environment', '-')).'</td></tr>'
			.'<tr><td>Server</td><td>'.self::e(self::value($environment, 'server', '-')).'</td></tr>';

		$tabs = ['request' => 'Request', 'timing' => 'Timing', 'database' => 'Database', 'environment' => 'Environment'];
		$tabButtons = '';
		$panels = '';
		$first = true;

		foreach ($tabs as $id => $label)
		{
			$active = $first ? ' ci-active' : '';
			$tabButtons .= '<button type="button" class="ci-tab'.$active.'" data-ci-tab="'.$id.'">'.self::e($label).'</button>';

			$body = match ($id)
			{
				'request'     => $requestRows,
				'timing'      => $timingRows,
				'database'    => '<table><thead><tr><th>Query</th><th class="ci-num">Time</th></tr></thead><tbody>'.$databaseRows.'</tbody></table>',
				'environment' => $environmentRows,
			};

			$panels .= '<div class="ci-panel'.$active.'" data-ci-panel="'.$id.'">'.$body.'</div>';
			$first = false;
		}

		return
			'<div id="ci-toolbar">'
			.'<button type="button" id="ci-toolbar-toggle" title="Customigniter debug toolbar">CI · '.self::e($summary).'</button>'
			.'<div id="ci-toolbar-panel" hidden>'
			.'<div class="ci-toolbar-tabs">'.$tabButtons.'</div>'
			.$panels
			.'</div></div>';
	}

	// --------------------------------------------------------------------

	/**
	 * Panel styles
	 *
	 * @return	string
	 */
	private static function style(): string
	{
		return '<style>'
			.'#ci-toolbar{position:fixed;right:12px;bottom:12px;z-index:99999;font:12px/1.5 ui-monospace,SFMono-Regular,Menlo,Consolas,monospace;color:#e6edf3}'
			.'#ci-toolbar-toggle{background:#161b22;color:#e6edf3;border:1px solid #30363d;border-radius:6px;padding:6px 10px;cursor:pointer;box-shadow:0 4px 12px rgba(0,0,0,.4)}'
			.'#ci-toolbar-toggle:hover{border-color:#58a6ff}'
			.'#ci-toolbar-panel{position:fixed;right:12px;bottom:44px;width:min(720px,calc(100vw - 24px));max-height:60vh;overflow:auto;background:#0d1117;border:1px solid #30363d;border-radius:8px;box-shadow:0 8px 30px rgba(0,0,0,.55)}'
			.'.ci-toolbar-tabs{display:flex;gap:4px;padding:8px;border-bottom:1px solid #21262d;position:sticky;top:0;background:#0d1117}'
			.'.ci-tab{background:transparent;color:#8b949e;border:1px solid transparent;border-radius:5px;padding:3px 10px;cursor:pointer;font:inherit}'
			.'.ci-tab:hover{color:#e6edf3}'
			.'.ci-tab.ci-active{color:#1f6feb;background:#1f6feb1a;border-color:#1f6feb55}'
			.'.ci-panel{padding:10px 12px;display:none}'
			.'.ci-panel.ci-active{display:block}'
			.'#ci-toolbar table{width:100%;border-collapse:collapse}'
			.'#ci-toolbar th,#ci-toolbar td{text-align:left;vertical-align:top;padding:4px 8px;border-bottom:1px solid #21262d}'
			.'#ci-toolbar th{color:#8b949e;font-weight:600}'
			.'.ci-num{text-align:right;white-space:nowrap;color:#8b949e}'
			.'.ci-q{word-break:break-all;color:#a5d6ff}'
			.'.ci-empty{color:#8b949e;font-style:italic}'
			.'</style>';
	}

	// --------------------------------------------------------------------

	/**
	 * Panel behaviour script
	 *
	 * @return	string
	 */
	private static function script(): string
	{
		return '<script>'
			.'(function(){'
			.'var t=document.getElementById("ci-toolbar-toggle"),p=document.getElementById("ci-toolbar-panel");'
			.'if(t&&p){t.addEventListener("click",function(){p.hidden=!p.hidden;});}'
			.'document.querySelectorAll("#ci-toolbar .ci-tab").forEach(function(b){'
			.'b.addEventListener("click",function(){'
			.'var id=b.getAttribute("data-ci-tab");'
			.'document.querySelectorAll("#ci-toolbar .ci-tab").forEach(function(x){x.classList.remove("ci-active");});'
			.'document.querySelectorAll("#ci-toolbar .ci-panel").forEach(function(x){x.classList.remove("ci-active");});'
			.'b.classList.add("ci-active");'
			.'var panel=document.querySelector(\'#ci-toolbar .ci-panel[data-ci-panel="\'+id+\'"]\');'
			.'if(panel){panel.classList.add("ci-active");}'
			.'});});'
			.'})();'
			.'</script>';
	}

	// --------------------------------------------------------------------

	/**
	 * Read a string-ish value from a data section
	 *
	 * @param	array<array-key, mixed>		$data
	 * @param	string						$key
	 * @param	string						$default
	 * @return	string
	 */
	private static function value(array $data, string $key, string $default): string
	{
		$value = $data[$key] ?? $default;

		if (is_string($value))
		{
			return $value;
		}

		if (is_int($value) OR is_float($value))
		{
			return (string) $value;
		}

		return $default;
	}

	// --------------------------------------------------------------------

	/**
	 * Escape text for HTML output
	 *
	 * @param	string	$text
	 * @return	string
	 */
	private static function e(string $text): string
	{
		return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
	}
}
