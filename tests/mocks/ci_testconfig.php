<?php

class CI_TestConfig extends CI_Config {

	public array $config = [];
	public array $_config_paths = [APPPATH];
	public array $loaded = [];

	public function item($key, $index = '')
	{
		return $this->config[$key] ?? FALSE;
	}

	public function load($file = '', $use_sections = FALSE, $fail_gracefully = FALSE)
	{
		$this->loaded[] = $file;
		return TRUE;
	}

}
