<?php
declare(strict_types=1);

use Customigniter\Events\EventDispatcher;
use Customigniter\Events\Events;
use PHPUnit\Framework\TestCase;

class EventDispatcherTest extends TestCase
{
	// --------------------------------------------------------------------

	protected function setUp(): void
	{
		Events::reset();
	}

	// --------------------------------------------------------------------

	protected function tearDown(): void
	{
		Events::reset();
	}

	// --------------------------------------------------------------------

	public function test_listeners_run_in_priority_order(): void
	{
		$dispatcher = new EventDispatcher();
		$order = [];

		$dispatcher->on('evt', static function () use (&$order): void {
			$order[] = 'low';
		}, -5);
		$dispatcher->on('evt', static function () use (&$order): void {
			$order[] = 'high';
		}, 10);
		$dispatcher->on('evt', static function () use (&$order): void {
			$order[] = 'normal';
		});

		$dispatcher->dispatch('evt');

		$this->assertSame(['high', 'normal', 'low'], $order);
	}

	// --------------------------------------------------------------------

	public function test_equal_priority_keeps_registration_order(): void
	{
		$dispatcher = new EventDispatcher();
		$order = [];

		foreach (['first', 'second', 'third'] as $name) {
			$dispatcher->on('evt', static function () use (&$order, $name): void {
				$order[] = $name;
			});
		}

		$dispatcher->dispatch('evt');

		$this->assertSame(['first', 'second', 'third'], $order);
	}

	// --------------------------------------------------------------------

	public function test_dispatch_passes_payload_to_listeners(): void
	{
		$dispatcher = new EventDispatcher();
		$received = [];

		$dispatcher->on('evt', static function (array $data) use (&$received): void {
			$received[] = $data;
		});

		$dispatcher->dispatch('evt', ['hook' => 'pre_system']);

		$this->assertSame([['hook' => 'pre_system']], $received);
	}

	// --------------------------------------------------------------------

	public function test_off_removes_specific_and_all_listeners(): void
	{
		$dispatcher = new EventDispatcher();
		$first = static function (): void {
		};
		$second = static function (): void {
		};

		$dispatcher->on('evt', $first);
		$dispatcher->on('evt', $second);
		$dispatcher->off('evt', $first);

		$this->assertCount(1, $dispatcher->listeners('evt'));

		$dispatcher->off('evt');
		$this->assertFalse($dispatcher->hasListeners('evt'));
	}

	// --------------------------------------------------------------------

	public function test_clear_removes_listeners(): void
	{
		$dispatcher = new EventDispatcher();
		$dispatcher->on('one', static function (): void {
		});
		$dispatcher->on('two', static function (): void {
		});

		$dispatcher->clear('one');
		$this->assertFalse($dispatcher->hasListeners('one'));
		$this->assertTrue($dispatcher->hasListeners('two'));

		$dispatcher->clear();
		$this->assertFalse($dispatcher->hasListeners('two'));
	}

	// --------------------------------------------------------------------

	public function test_listener_exceptions_propagate(): void
	{
		$dispatcher = new EventDispatcher();
		$dispatcher->on('evt', static function (): void {
			throw new RuntimeException('listener failed');
		});

		$this->expectException(RuntimeException::class);
		$this->expectExceptionMessage('listener failed');

		$dispatcher->dispatch('evt');
	}

	// --------------------------------------------------------------------

	public function test_facade_delegates_to_shared_dispatcher(): void
	{
		$received = null;

		Events::on('evt', static function (array $data) use (&$received): void {
			$received = $data;
		});

		$this->assertTrue(Events::has('evt'));

		Events::trigger('evt', ['source' => 'test']);

		$this->assertSame(['source' => 'test'], $received);
	}

	// --------------------------------------------------------------------

	public function test_facade_set_dispatcher_replaces_instance(): void
	{
		$dispatcher = new EventDispatcher();
		Events::setDispatcher($dispatcher);

		$this->assertSame($dispatcher, Events::dispatcher());
	}
}
