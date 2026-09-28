<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Feature test fixture: _remap support
 */
class Remap extends CI_Controller {

	public function _remap($method, ...$args)
	{
		echo 'remap:'.$method.':'.implode(',', $args);
	}
}
