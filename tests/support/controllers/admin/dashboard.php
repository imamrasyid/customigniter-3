<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Feature test fixture: subdirectory controller
 */
class Dashboard extends CI_Controller {

	public function index()
	{
		echo 'admin dashboard';
	}

	public function stats()
	{
		echo 'stats';
	}
}
