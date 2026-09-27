<?php
declare(strict_types=1);

namespace Customigniter\Container;

/**
 * Config Service Provider
 *
 * Bridges CodeIgniter's CI_Config into the ServiceContainer.
 * Registers config values as accessible services, enabling typed
 * configuration access via the DI container.
 *
 * @package	Customigniter3
 */
class ConfigServiceProvider implements ServiceProviderInterface
{
	/**
	 * Config sections to register
	 *
	 * @var array<int, string>
	 */
	private array $sections;

	/**
	 * Constructor
	 *
	 * @param	array<int, string>	$sections	Config section names to register (empty = all)
	 */
	public function __construct(array $sections = [])
	{
		$this->sections = $sections;
	}

	// --------------------------------------------------------------------

	/**
	 * Register CI config items into the container
	 *
	 * Registers:
	 * - 'config' => full CI_Config instance
	 * - 'config.{key}' => individual config values
	 *
	 * @param	ServiceContainer	$container
	 * @return	void
	 */
	public function register(ServiceContainer $container): void
	{
		// Register the CI_Config instance itself
		$container->singleton('config', static function (): \CI_Config {
			return get_instance()->config;
		});

		// Register individual config items
		$config = &get_config();

		$keys = ! empty($this->sections)
			? array_intersect_key($config, array_flip($this->sections))
			: $config;

		foreach ($keys as $key => $value) {
			$key = (string) $key;
			$container->set("config.{$key}", static function () use ($value): mixed {
				return $value;
			});
		}
	}
}
