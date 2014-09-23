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

$loader = require(__DIR__ . '/../../vendor/autoload.php');
$loader->addPsr4('OpenExam\\Tests\\', __DIR__ . '/support');

Phalcon\DI::setDefault($di);
