<?php
/**
 * Definitions and Config
 */

define('ROOT_PATH', rtrim(dirname(__DIR__), '/'));
define('ENV_FILE', ROOT_PATH.'/.env');
define('CLASSES_PATH', ROOT_PATH.'/classes');
define('VIEWS_PATH', ROOT_PATH.'/views');
define('ASSETS_PATH', ROOT_PATH.'/assets');
define('ROOT_URI', '/');
define('APP_HOST', 'https://'.$_SERVER['HTTP_HOST']);
define('ASSETS_URI', ROOT_URI.'assets');
