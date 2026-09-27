<?php
/**
 * PHPStan bootstrap: defines framework constants that exist only at
 * runtime so the analyser can evaluate code that references them.
 * This file runs inside the PHPStan process only — never in the app.
 */

defined('BASEPATH') || define('BASEPATH', __DIR__ . '/../system/');
defined('APPPATH') || define('APPPATH', __DIR__ . '/../application/');
defined('VIEWPATH') || define('VIEWPATH', APPPATH . 'views/');
defined('ENVIRONMENT') || define('ENVIRONMENT', 'testing');
defined('FCPATH') || define('FCPATH', __DIR__ . '/../');
defined('CACHE_PATH') || define('CACHE_PATH', BASEPATH . 'cache/');
defined('LOG_PATH') || define('LOG_PATH', APPPATH . 'logs/');
