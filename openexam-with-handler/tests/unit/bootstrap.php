<?php

// 
// PHP unit bootstrap for Phalcon MVC.
// 

ini_set('display_errors', 1);
error_reporting(E_ALL);

define('BASE_DIR', realpath(__DIR__ . '/../..'));
define('APP_DIR', BASE_DIR . '/app');

$config = include(APP_DIR . '/config/config.php');

include APP_DIR . "/config/loader.php";
include APP_DIR . "/config/services.php";
