<?php
declare(strict_types=1);

namespace Customigniter\View;

/**
 * View rendering engine with layouts, sections and components
 *
 * Views are plain PHP files. Inside a view $this refers to this engine,
 * so $this->e() escapes, $this->start()/stop() capture sections and
 * $this->include()/component() render partials.
 *
 * @package	Customigniter3
 */
class View
{
	/**
	 * View directories, searched in order
	 *
	 * @var	array<int, string>
	 */
	private array $paths;

	/**
	 * Named rendered sections
	 *
	 * @var	array<string, string>
	 */
	private array $sections = [];

	/**
	 * Names of the sections currently being captured
	 *
	 * @var	array<int, string>
	 */
	private array $sectionStack = [];

	/**
	 * Data of the renders currently in progress (for preserveData includes)
	 *
	 * @var	array<int, array<string, mixed>>
	 */
	private array $dataStack = [];

	/**
	 * Shared instance used by the static facade methods
	 */
	private static ?self $instance = null;

	// --------------------------------------------------------------------

	/**
	 * Constructor
	 *
	 * @param	array<int, string>	$paths	Extra view paths; defaults to VIEWPATH
	 */
	public function __construct(array $paths = [])
	{
		if ($paths === [] && defined('VIEWPATH'))
		{
			$paths = [VIEWPATH];
		}

		$this->paths = $paths;
	}

	// --------------------------------------------------------------------

	/**
	 * Shared instance for facade-style usage
	 */
	public static function instance(): self
	{
		return self::$instance ??= new self();
	}

	// --------------------------------------------------------------------

	/**
	 * Drop the shared instance (used by tests)
	 */
	public static function reset(): void
	{
		self::$instance = null;
	}

	// --------------------------------------------------------------------

	/**
	 * Add a view directory
	 *
	 * @param	string	$path	Directory to search for views
	 * @param	bool	$prepend	Search before the existing paths
	 * @return	void
	 */
	public function addPath(string $path, bool $prepend = true): void
	{
		if ($prepend)
		{
			array_unshift($this->paths, $path);
		}
		else
		{
			$this->paths[] = $path;
		}
	}

	// --------------------------------------------------------------------

	/**
	 * Configured view paths
	 *
	 * @return	array<int, string>
	 */
	public function getPaths(): array
	{
		return $this->paths;
	}

	// --------------------------------------------------------------------

	/**
	 * Render a view, optionally wrapped in a layout
	 *
	 * Options:
	 * - layout: view name rendered around the content
	 * - section: section name the content is stored under (default 'content')
	 *
	 * @param	string					$view	View name relative to a view path
	 * @param	array<string, mixed>	$data	Data extracted into the view
	 * @param	array<string, mixed>	$options	Rendering options
	 * @return	string
	 */
	public function render(string $view, array $data = [], array $options = []): string
	{
		$content = $this->evaluate($this->resolve($view), $data);

		if ( ! isset($options['layout']))
		{
			return $content;
		}

		if ( ! is_string($options['layout']))
		{
			throw new ViewException('The "layout" option must be a string');
		}

		$section = isset($options['section']) && is_string($options['section'])
			? $options['section']
			: 'content';

		$this->sections[$section] = $content;

		return $this->evaluate($this->resolve($options['layout']), $data);
	}

	// --------------------------------------------------------------------

	/**
	 * Render a partial view
	 *
	 * By default the data of the surrounding render is inherited
	 * (preserveData); pass ['preserveData' => false] to opt out.
	 *
	 * @param	string					$view	View name relative to a view path
	 * @param	array<string, mixed>	$data	Extra data for the partial
	 * @param	array<string, mixed>	$options	Include options
	 * @return	string
	 */
	public function include(string $view, array $data = [], array $options = []): string
	{
		$preserve = ! array_key_exists('preserveData', $options) || $options['preserveData'] !== false;

		if ($preserve && $this->dataStack !== [])
		{
			$parent = $this->dataStack[count($this->dataStack) - 1];
			$data = array_merge($parent, $data);
		}

		return $this->evaluate($this->resolve($view), $data);
	}

	// --------------------------------------------------------------------

	/**
	 * Render a component; the slot content is exposed as $slot
	 *
	 * @param	string					$view	Component view name
	 * @param	array<string, mixed>	$data	Component data
	 * @param	string					$slot	Slot markup (escape untrusted values yourself)
	 * @return	string
	 */
	public function component(string $view, array $data = [], string $slot = ''): string
	{
		$data['slot'] = $slot;

		return $this->include($view, $data, ['preserveData' => false]);
	}

	// --------------------------------------------------------------------

	/**
	 * Start capturing a named section
	 *
	 * @param	string	$name	Section name
	 * @return	void
	 */
	public function start(string $name = 'content'): void
	{
		$this->sectionStack[] = $name;
		ob_start();
	}

	// --------------------------------------------------------------------

	/**
	 * Finish the current section and store its output
	 *
	 * @return	void
	 */
	public function stop(): void
	{
		if ($this->sectionStack === [])
		{
			throw new ViewException('stop() was called without a matching start()');
		}

		$buffer = ob_get_clean();

		if ($buffer === false)
		{
			throw new ViewException('No output buffer is active');
		}

		$name = array_pop($this->sectionStack);
		$this->sections[$name] = $buffer;
	}

	// --------------------------------------------------------------------

	/**
	 * Return a stored section, or the default when it is empty
	 *
	 * @param	string	$name	Section name
	 * @param	string	$default	Returned when the section has no output
	 * @return	string
	 */
	public function yieldContent(string $name = 'content', string $default = ''): string
	{
		return $this->sections[$name] ?? $default;
	}

	// --------------------------------------------------------------------

	/**
	 * Alias of yieldContent() using the CodeIgniter 4 name
	 *
	 * @param	string	$name	Section name
	 * @param	string	$default	Returned when the section has no output
	 * @return	string
	 */
	public function renderSection(string $name, string $default = ''): string
	{
		return $this->yieldContent($name, $default);
	}

	// --------------------------------------------------------------------

	/**
	 * Escape a scalar value for HTML output
	 *
	 * @param	mixed	$value	Value to escape
	 * @return	string
	 */
	public function e(mixed $value): string
	{
		if ($value === null)
		{
			return '';
		}

		if (is_bool($value))
		{
			return $value ? '1' : '';
		}

		if ( ! is_string($value) && ! is_int($value) && ! is_float($value))
		{
			throw new ViewException('Only scalar values can be escaped');
		}

		return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
	}

	// --------------------------------------------------------------------

	/**
	 * Resolve a view name to a file inside the configured paths
	 *
	 * @param	string	$view	View name, with or without the .php extension
	 * @return	string
	 */
	private function resolve(string $view): string
	{
		$relative = str_replace('\\', '/', $view);

		if (str_contains($relative, '..'))
		{
			throw new ViewException('View names may not contain "..": '.$view);
		}

		if (pathinfo($relative, PATHINFO_EXTENSION) === '')
		{
			$relative .= '.php';
		}

		$relative = str_replace('/', DIRECTORY_SEPARATOR, $relative);

		foreach ($this->paths as $path)
		{
			$candidate = rtrim($path, '/\\').DIRECTORY_SEPARATOR.$relative;

			if (is_file($candidate))
			{
				return $candidate;
			}
		}

		throw new ViewException('View not found: '.$view);
	}

	// --------------------------------------------------------------------

	/**
	 * Include a view file with the given data and capture its output
	 *
	 * @param	string					$file	Absolute path to the view file
	 * @param	array<string, mixed>	$data	Data extracted into the view
	 * @return	string
	 */
	private function evaluate(string $file, array $data): string
	{
		$this->dataStack[] = $data;

		try
		{
			extract($data, EXTR_SKIP);
			ob_start();

			try
			{
				include $file;
			}
			finally
			{
				$buffer = ob_get_clean();

				if ($buffer === false)
				{
					throw new ViewException('Failed to capture view output: '.$file);
				}
			}
		}
		finally
		{
			array_pop($this->dataStack);
		}

		return $buffer;
	}
}
