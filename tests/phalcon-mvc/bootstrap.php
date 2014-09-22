<?php

// 
// PHP unit bootstrap for Phalcon MVC.
// 

ini_set('display_errors', 1);
error_reporting(E_ALL);

define('CONFIG_PHP', __DIR__ . '/../../phalcon-mvc/app/config/system/config.php');

$config = include(CONFIG_PHP);

include CONFIG_SYS . "/loader.php";
include CONFIG_SYS . "/services.php";

Phalcon\DI::setDefault($di);
