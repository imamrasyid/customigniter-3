<?php
declare(strict_types=1);

namespace Customigniter\Container;

/**
 * Lightweight Service Container
 *
 * PSR-11 inspired DI container with no external dependencies.
 * Supports factory closures, singletons, and auto-wiring via reflection.
 *
 * @package	Customigniter3
 */
class ServiceContainer
{
	/**
	 * Registered services (factory closures or resolved values)
	 *
	 * @var array<string, mixed>
	 */
	private array $services = [];

	/**
	 * Resolved singleton instances
	 *
	 * @var array<string, mixed>
	 */
	private array $resolved = [];

	/**
	 * IDs registered as singletons (result is cached after resolution)
	 *
	 * @var array<string, bool>
	 */
	private array $singletons = [];

	/**
	 * Service extensions (middleware applied after resolution)
	 *
	 * @var array<string, array<int, callable>>
	 */
	private array $extenders = [];

	/**
	 * Class names currently being auto-wired (circular dependency detection)
	 *
	 * @var array<string, bool>
	 */
	private array $building = [];

	/**
	 * Service IDs currently being resolved (circular dependency detection)
	 *
	 * @var array<string, bool>
	 */
	private array $resolving = [];

	// --------------------------------------------------------------------

	/**
	 * Register a service as a factory closure
	 *
	 * The closure is called each time the service is requested.
	 *
	 * @param	string	$id
	 * @param callable(ServiceContainer): mixed $factory
	 * @return	static
	 */
	public function set(string $id, callable $factory): static
	{
		$this->services[$id] = $factory;
		$this->singletons[$id] = false;
		unset($this->resolved[$id]);

		return $this;
	}

	// --------------------------------------------------------------------

	/**
	 * Register a singleton service
	 *
	 * The factory is called once; the extended result is cached for
	 * subsequent calls. Extenders registered via extend() are applied
	 * before the result is cached, so every get() returns the same
	 * extended instance.
	 *
	 * @param	string	$id
	 * @param callable(ServiceContainer): mixed $factory
	 * @return	static
	 */
	public function singleton(string $id, callable $factory): static
	{
		$this->services[$id] = $factory;
		$this->singletons[$id] = true;
		unset($this->resolved[$id]);

		return $this;
	}

	// --------------------------------------------------------------------

	/**
	 * Register an already-resolved value
	 *
	 * @param	string	$id
	 * @param	mixed	$value
	 * @return	static
	 */
	public function instance(string $id, mixed $value): static
	{
		$this->services[$id] = static fn (ServiceContainer $c): mixed => $value;
		$this->singletons[$id] = true;
		unset($this->resolved[$id]);

		return $this;
	}

	// --------------------------------------------------------------------

	/**
	 * Extend an existing service
	 *
	 * The extension closure receives the resolved instance and must return it
	 * (optionally modified). Extensions are applied in registration order.
	 *
	 * @param	string	$id
	 * @param callable(mixed, ServiceContainer): mixed $extender
	 * @return	static
	 */
	public function extend(string $id, callable $extender): static
	{
		$this->extenders[$id][] = $extender;

		return $this;
	}

	// --------------------------------------------------------------------

	/**
	 * Resolve a service by ID
	 *
	 * Extenders are applied after the factory runs. For singletons and
	 * pre-registered instances the extended result is cached, so every
	 * subsequent get() returns the same extended value. Plain factories
	 * are re-created (and re-extended) on each call.
	 *
	 * @param	string	$id
	 * @return	mixed
	 * @throws	\RuntimeException
	 */
	public function get(string $id): mixed
	{
		if (array_key_exists($id, $this->resolved)) {
			return $this->resolved[$id];
		}

		if ( ! isset($this->services[$id])) {
			throw new \RuntimeException("Service '{$id}' is not registered.");
		}

		if (isset($this->resolving[$id])) {
			throw new \RuntimeException("Circular dependency detected while resolving service '{$id}'.");
		}

		$this->resolving[$id] = true;

		try {
			$service = ($this->services[$id])($this);

			if (isset($this->extenders[$id])) {
				foreach ($this->extenders[$id] as $extender) {
					$service = $extender($service, $this);
				}
			}
		}
		finally {
			unset($this->resolving[$id]);
		}

		if ( ! empty($this->singletons[$id])) {
			$this->resolved[$id] = $service;
		}

		return $service;
	}

	// --------------------------------------------------------------------

	/**
	 * Check if a service is registered
	 *
	 * @param	string	$id
	 * @return	bool
	 */
	public function has(string $id): bool
	{
		return isset($this->services[$id]);
	}

	// --------------------------------------------------------------------

	/**
	 * Auto-wire a class using reflection
	 *
	 * Resolves constructor dependencies from the container. Falls back to
	 * default parameter values when dependencies are not registered.
	 * Detects circular constructor dependencies.
	 *
	 * @param	string	$className
	 * @return	object
	 * @throws	\RuntimeException
	 * @throws	\ReflectionException
	 */
	public function build(string $className): object
	{
		if (isset($this->building[$className])) {
			throw new \RuntimeException("Circular dependency detected while auto-wiring: {$className}");
		}

		$ref = new \ReflectionClass($className);

		if ($ref->isAbstract() || $ref->isInterface()) {
			throw new \RuntimeException("Cannot auto-wire abstract class or interface: {$className}");
		}

		$constructor = $ref->getConstructor();

		if ($constructor === null) {
			return new $className();
		}

		$parameters = $constructor->getParameters();
		$args = [];
		$this->building[$className] = true;

		try {
			foreach ($parameters as $param) {
				$name = $param->getName();

				// Try resolving by parameter name first
				if ($this->has($name)) {
					$args[] = $this->get($name);
					continue;
				}

				// Then by type: every non-builtin candidate (handles union types)
				$found = false;
				foreach ($this->typeCandidates($param->getType()) as $candidate) {
					if ($this->has($candidate)) {
						$args[] = $this->get($candidate);
						$found = true;
						break;
					}
				}

				if ($found) {
					continue;
				}

				if ($param->isDefaultValueAvailable()) {
					$args[] = $param->getDefaultValue();
				}
				else {
					throw new \RuntimeException(
						"Cannot resolve parameter '\${$name}' of {$className}::__construct()"
					);
				}
			}
		}
		finally {
			unset($this->building[$className]);
		}

		return $ref->newInstanceArgs($args);
	}

	// --------------------------------------------------------------------

	/**
	 * Extract non-builtin class names from a parameter type
	 *
	 * Supports named types, nullable types and union types.
	 *
	 * @param	\ReflectionType|null	$type
	 * @return	array<int, string>
	 */
	private function typeCandidates(?\ReflectionType $type): array
	{
		if ($type === null) {
			return [];
		}

		$candidates = [];

		foreach ($type instanceof \ReflectionUnionType ? $type->getTypes() : [$type] as $subType) {
			if ($subType instanceof \ReflectionNamedType) {
				if ( ! $subType->isBuiltin()) {
					$candidates[] = $subType->getName();
				}
			}
			elseif ($subType instanceof \ReflectionUnionType || $subType instanceof \ReflectionIntersectionType) {
				// Intersection types cannot be container-resolved by name.
				continue;
			}
		}

		return $candidates;
	}

	// --------------------------------------------------------------------

	/**
	 * Register a service provider
	 *
	 * @param	ServiceProviderInterface	$provider
	 * @return	static
	 */
	public function register(ServiceProviderInterface $provider): static
	{
		$provider->register($this);

		return $this;
	}

	// --------------------------------------------------------------------

	/**
	 * Flush all registered and resolved services
	 *
	 * @return	void
	 */
	public function flush(): void
	{
		$this->services = [];
		$this->resolved = [];
		$this->singletons = [];
		$this->extenders = [];
		$this->building = [];
		$this->resolving = [];
	}
}
