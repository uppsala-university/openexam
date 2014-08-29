<?php

// 
// PHP unit bootstrap for Phalcon MVC.
// 

ini_set('display_errors', 1);
error_reporting(E_ALL);

define('CONFIG_DIR', realpath(__DIR__ . '/../../app/config'));

$config = include(CONFIG_DIR . '/config.php');

include CONFIG_DIR . "/loader.php";
include CONFIG_DIR . "/services.php";
