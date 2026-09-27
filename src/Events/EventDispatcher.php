<?php
declare(strict_types=1);

namespace Customigniter\Events;

/**
 * Event Dispatcher
 *
 * A tiny, dependency-free event system (PSR-14 inspired) used to
 * extend the framework without touching core code. Listeners are
 * invoked in priority order (higher priority first); ties keep
 * registration order. Listener exceptions propagate to the caller,
 * so they surface through the regular exception handler.
 *
 * @package	Customigniter3
 */
final class EventDispatcher
{
	/**
	 * Registered listeners per event
	 *
	 * Each entry: [priority, sequence, listener]
	 *
	 * @var array<string, list<array{0: int, 1: int, 2: callable}>>
	 */
	private array $listeners = [];

	/**
	 * Monotonic sequence used to keep registration order stable
	 *
	 * @var int
	 */
	private int $sequence = 0;

	// --------------------------------------------------------------------

	/**
	 * Register a listener for an event
	 *
	 * @param	string	$event		Event name, e.g. 'pre_system'
	 * @param	callable	$listener	Listener: fn(array $data): void
	 * @param	int	$priority	Highest priority runs first (default 0)
	 * @return	void
	 */
	public function on(string $event, callable $listener, int $priority = 0): void
	{
		$this->listeners[$event][] = [$priority, $this->sequence++, $listener];
	}

	// --------------------------------------------------------------------

	/**
	 * Remove listeners
	 *
	 * @param	string	$event	Event name
	 * @param	callable|null	$listener	Specific listener to remove (NULL = all)
	 * @return	void
	 */
	public function off(string $event, ?callable $listener = null): void
	{
		if ( ! isset($this->listeners[$event]))
		{
			return;
		}

		if ($listener === NULL)
		{
			unset($this->listeners[$event]);
			return;
		}

		$this->listeners[$event] = array_values(array_filter(
			$this->listeners[$event],
			static fn (array $entry): bool => $entry[2] !== $listener
		));

		if ($this->listeners[$event] === [])
		{
			unset($this->listeners[$event]);
		}
	}

	// --------------------------------------------------------------------

	/**
	 * Dispatch an event to its listeners
	 *
	 * @param	string	$event	Event name
	 * @param	array<string, mixed>	$data	Payload passed to every listener
	 * @return	void
	 */
	public function dispatch(string $event, array $data = []): void
	{
		foreach ($this->sorted($event) as $listener)
		{
			$listener($data);
		}
	}

	// --------------------------------------------------------------------

	/**
	 * Whether the event has at least one listener
	 *
	 * @param	string	$event
	 * @return	bool
	 */
	public function hasListeners(string $event): bool
	{
		return ! empty($this->listeners[$event]);
	}

	// --------------------------------------------------------------------

	/**
	 * Get the event's listeners in invocation order
	 *
	 * @param	string	$event
	 * @return	list<callable>
	 */
	public function listeners(string $event): array
	{
		return $this->sorted($event);
	}

	// --------------------------------------------------------------------

	/**
	 * Remove all listeners for one event, or for every event
	 *
	 * @param	string|null	$event
	 * @return	void
	 */
	public function clear(?string $event = null): void
	{
		if ($event === NULL)
		{
			$this->listeners = [];
			return;
		}

		unset($this->listeners[$event]);
	}

	// --------------------------------------------------------------------

	/**
	 * Get listeners sorted by priority (desc), then registration order
	 *
	 * @param	string	$event
	 * @return	list<callable>
	 */
	private function sorted(string $event): array
	{
		if ( ! isset($this->listeners[$event]))
		{
			return [];
		}

		$entries = $this->listeners[$event];

		usort($entries, static fn (array $a, array $b): int
			=> $b[0] <=> $a[0] ?: $a[1] <=> $b[1]
		);

		return array_map(static fn (array $entry): callable => $entry[2], $entries);
	}
}
