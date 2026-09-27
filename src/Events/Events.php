<?php
declare(strict_types=1);

namespace Customigniter\Events;

/**
 * Static Event Facade
 *
 * Convenience entry point for the application-wide dispatcher.
 * Mirrors the ergonomics of CI4's Events class:
 *
 *   Events::on('pre_system', function (array $data): void { ... });
 *   Events::dispatch('pre_system');
 *
 * The framework bridges every legacy hook point (pre_system,
 * pre_controller, post_controller, ...) onto this dispatcher, so
 * listeners can subscribe even when no hook is configured.
 *
 * @package	Customigniter3
 */
final class Events
{
	/**
	 * Shared dispatcher instance
	 *
	 * @var EventDispatcher|null
	 */
	private static ?EventDispatcher $dispatcher = null;

	// --------------------------------------------------------------------

	/**
	 * Get (and lazily create) the shared dispatcher
	 *
	 * @return	EventDispatcher
	 */
	public static function dispatcher(): EventDispatcher
	{
		return self::$dispatcher ??= new EventDispatcher();
	}

	// --------------------------------------------------------------------

	/**
	 * Register a listener
	 *
	 * @param	string	$event
	 * @param	callable	$listener
	 * @param	int	$priority
	 * @return	void
	 */
	public static function on(string $event, callable $listener, int $priority = 0): void
	{
		self::dispatcher()->on($event, $listener, $priority);
	}

	// --------------------------------------------------------------------

	/**
	 * Remove listeners (all listeners of the event when $listener is NULL)
	 *
	 * @param	string	$event
	 * @param	callable|null	$listener
	 * @return	void
	 */
	public static function off(string $event, ?callable $listener = null): void
	{
		self::dispatcher()->off($event, $listener);
	}

	// --------------------------------------------------------------------

	/**
	 * Dispatch an event
	 *
	 * @param	string	$event
	 * @param	array<string, mixed>	$data
	 * @return	void
	 */
	public static function dispatch(string $event, array $data = []): void
	{
		self::dispatcher()->dispatch($event, $data);
	}

	// --------------------------------------------------------------------

	/**
	 * Alias of dispatch() (verb used by CI4)
	 *
	 * @param	string	$event
	 * @param	array<string, mixed>	$data
	 * @return	void
	 */
	public static function trigger(string $event, array $data = []): void
	{
		self::dispatcher()->dispatch($event, $data);
	}

	// --------------------------------------------------------------------

	/**
	 * Whether the event has listeners
	 *
	 * @param	string	$event
	 * @return	bool
	 */
	public static function has(string $event): bool
	{
		return self::dispatcher()->hasListeners($event);
	}

	// --------------------------------------------------------------------

	/**
	 * Replace the shared dispatcher (used by tests)
	 *
	 * @param	EventDispatcher	$dispatcher
	 * @return	void
	 */
	public static function setDispatcher(EventDispatcher $dispatcher): void
	{
		self::$dispatcher = $dispatcher;
	}

	// --------------------------------------------------------------------

	/**
	 * Drop the shared dispatcher
	 *
	 * @return	void
	 */
	public static function reset(): void
	{
		self::$dispatcher = null;
	}
}
