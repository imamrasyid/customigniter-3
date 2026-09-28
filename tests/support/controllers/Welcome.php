<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Feature test fixture controller
 */
class Welcome extends CI_Controller {

	public function index()
	{
		echo 'Welcome to Customigniter!';
	}

	public function greet($name = 'world')
	{
		return 'Hello '.$name;
	}

	public function leak()
	{
		echo 'before';

		return 'after';
	}

	public function jsonResponse()
	{
		return (new Customigniter\Http\Response())->json(['ok' => TRUE, 'name' => 'ci']);
	}

	public function missing()
	{
		return (new Customigniter\Http\Response())->status(404)->body('not found');
	}

	public function go()
	{
		return (new Customigniter\Http\Response())->redirect('/login');
	}

	public function boom()
	{
		show_404();
	}

	public function postEcho()
	{
		echo 'got:'.($_POST['name'] ?? '');
	}
}
