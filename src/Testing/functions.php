<?php
/**
 * Testing support functions
 *
 * Declared only when the PHPUnit bootstrap has not loaded
 * CodeIgniter.php (which defines get_instance() itself).
 *
 * @package	Customigniter3
 */

if ( ! function_exists('get_instance'))
{
	/**
	 * Reference to the CI_Controller singleton
	 *
	 * The core helper only documents an object return type, so callers
	 * must narrow with instanceof before touching controller properties.
	 *
	 * @return	object
	 */
	function &get_instance()
	{
		$instance = &CI_Controller::get_instance();

		if ( ! $instance instanceof CI_Controller)
		{
			throw new RuntimeException('No CI_Controller instance is available');
		}

		return $instance;
	}
}
