<?php
declare(strict_types=1);

namespace Customigniter\Container;

/**
 * Service Provider Interface
 *
 * Contract for classes that register services into the container.
 *
 * @package	Customigniter3
 */
interface ServiceProviderInterface
{
	/**
	 * Register services into the container
	 *
	 * @param	ServiceContainer	$container
	 * @return	void
	 */
	public function register(ServiceContainer $container): void;
}
