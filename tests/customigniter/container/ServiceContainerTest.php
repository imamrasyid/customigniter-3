<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use Customigniter\Container\ServiceContainer;
use Customigniter\Container\ServiceProviderInterface;

class ServiceContainerTest extends TestCase
{
	private ServiceContainer $container;

	protected function setUp(): void
	{
		$this->container = new ServiceContainer();
	}

	// --------------------------------------------------------------------

	public function test_set_and_get(): void
	{
		$this->container->set('foo', fn () => 'bar');
		$this->assertEquals('bar', $this->container->get('foo'));
	}

	// --------------------------------------------------------------------

	public function test_factory_returns_new_instance_each_time(): void
	{
		$count = 0;
		$this->container->set('counter', function () use (&$count): object {
			$count++;
			return new \stdClass();
		});

		$a = $this->container->get('counter');
		$b = $this->container->get('counter');

		$this->assertNotSame($a, $b);
		$this->assertEquals(2, $count);
	}

	// --------------------------------------------------------------------

	public function test_singleton_returns_same_instance(): void
	{
		$this->container->singleton('foo', fn () => new \stdClass());

		$a = $this->container->get('foo');
		$b = $this->container->get('foo');

		$this->assertSame($a, $b);
	}

	// --------------------------------------------------------------------

	public function test_instance_registers_resolved_value(): void
	{
		$obj = new \stdClass();
		$obj->name = 'test';
		$this->container->instance('foo', $obj);

		$this->assertSame($obj, $this->container->get('foo'));
	}

	// --------------------------------------------------------------------

	public function test_has_returns_true_when_registered(): void
	{
		$this->assertFalse($this->container->has('foo'));
		$this->container->set('foo', fn () => 'bar');
		$this->assertTrue($this->container->has('foo'));
	}

	// --------------------------------------------------------------------

	public function test_get_throws_on_unregistered_service(): void
	{
		$this->expectException(\RuntimeException::class);
		$this->expectExceptionMessage("Service 'nonexistent' is not registered");
		$this->container->get('nonexistent');
	}

	// --------------------------------------------------------------------

	public function test_extend_modifies_resolved_service(): void
	{
		$this->container->set('foo', fn () => 'bar');
		$this->container->extend('foo', function (mixed $val): string {
			$this->assertIsString($val);

			return strtoupper($val);
		});

		$this->assertEquals('BAR', $this->container->get('foo'));
	}

	// --------------------------------------------------------------------

	public function test_fluent_interface(): void
	{
		$result = $this->container
			->set('a', fn () => 1)
			->singleton('b', fn () => 2)
			->instance('c', 3);

		$this->assertSame($this->container, $result);
	}

	// --------------------------------------------------------------------

	public function test_flush_clears_all(): void
	{
		$this->container->set('foo', fn () => 'bar');
		$this->container->singleton('baz', fn () => 'qux');
		$this->container->flush();

		$this->assertFalse($this->container->has('foo'));
		$this->assertFalse($this->container->has('baz'));
	}

	// --------------------------------------------------------------------

	public function test_build_auto_wires_simple_class(): void
	{
		$this->container->set('dep', fn () => 'resolved');
		$obj = $this->container->build(SimpleDependency::class);

		$this->assertInstanceOf(SimpleDependency::class, $obj);
		$this->assertEquals('resolved', $obj->dep);
	}

	// --------------------------------------------------------------------

	public function test_build_without_constructor(): void
	{
		$obj = $this->container->build(NoConstructor::class);
		$this->assertInstanceOf(NoConstructor::class, $obj);
	}

	// --------------------------------------------------------------------

	public function test_build_abstract_throws(): void
	{
		$this->expectException(\RuntimeException::class);
		$this->container->build(AbstractClass::class);
	}

	// --------------------------------------------------------------------

	public function test_register_provider(): void
	{
		$provider = new class implements ServiceProviderInterface {
			public function register(ServiceContainer $container): void
			{
				$container->set('from_provider', fn () => 'registered');
			}
		};

		$this->container->register($provider);
		$this->assertEquals('registered', $this->container->get('from_provider'));
	}
}

// --------------------------------------------------------------------
// Test helper classes
// --------------------------------------------------------------------

class SimpleDependency
{
	public string $dep;

	public function __construct(string $dep)
	{
		$this->dep = $dep;
	}
}

class NoConstructor {}

abstract class AbstractClass {}
