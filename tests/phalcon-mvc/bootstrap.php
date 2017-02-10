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
ini_set("memory_limit", "1536M");
error_reporting(E_ALL ^ E_STRICT);

// 
// Configure for running unit test:
// 
define('VALIDATION_SKIP_UNIQUENESS_CHECK', true);
define('MODEL_ALWAYS_USE_MASTER_CONNECTION', true);
define('CONFIG_PHP', realpath(__DIR__ . '/../../phalcon-mvc/app/config/system/config.php'));

// 
// Include the standard system configuration:
// 
$config = include(CONFIG_PHP);

// 
// Disable generic caching:
// 
$config->cache->enable->xcache = false;
$config->cache->enable->apc = false;
$config->cache->enable->memcache = false;
$config->cache->enable->file = false;

// 
// Disable database caching:
// 
$config->dbread->params->adapter->cached = false;
$config->dbread->params->adapter->deferred = false;
$config->dbwrite->params->adapter->cached = false;
$config->dbwrite->params->adapter->deferred = false;

// 
// Disable audit:
// 
// $config->audit = false;

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
