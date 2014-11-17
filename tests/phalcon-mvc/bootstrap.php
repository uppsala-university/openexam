<?php

// 
// The source code is copyrighted, with equal shared rights, between the
// authors (see the file AUTHORS) and the OpenExam project, Uppsala University 
// unless otherwise explicit stated elsewhere.
// 
// File:    bootstrap.php
// Created: 2014-08-26 11:45:13
// 
// Author:  Anders Lövgren (QNET/BMC CompDept)
// 

/**
 * Bootstrap file for phpunit. 
 * 
 * Setup configuration for running unit test of parts of the system that 
 * might using the Phalcon MVC framework. The idea is to provide a runtime 
 * context for unit cases equal to running under the web server or from a 
 * command line task.
 */
ini_set('display_errors', 1);
error_reporting(E_ALL);

// 
// Include the standard system configuration:
// 
define('CONFIG_PHP', __DIR__ . '/../../phalcon-mvc/app/config/system/config.php');
$config = include(CONFIG_PHP);

include CONFIG_SYS . "/loader.php";
include CONFIG_SYS . "/services.php";

// 
// Add support classes to autoloader:
// 
$loader = require(__DIR__ . '/../../vendor/autoload.php');
$loader->addPsr4('OpenExam\\Tests\\', __DIR__ . '/support');

// 
// Set authenticated user to unit test runner:
// 
if (extension_loaded('posix')) {
        $di->set('user', new \OpenExam\Library\Security\User(
            posix_getpwuid(posix_geteuid())['name'], gethostname()
        ));
} else {
        $di->set('user', new \OpenExam\Library\Security\User(
            get_current_user(), gethostname()
        ));
}
