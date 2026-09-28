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
	 * @return	object
	 */
	function &get_instance()
	{
		return CI_Controller::get_instance();
	}
}
