<?php

// 
// The source code is copyrighted, with equal shared rights, between the
// authors (see the file AUTHORS) and the OpenExam project, Uppsala University 
// unless otherwise explicit stated elsewhere.
// 
// File:    tasks.php
// Created: 2014-09-08 17:17:49
// 
// Author:  Anders Lövgren (Computing Department at BMC, Uppsala University)
// 

/**
 * Configuration file for command line tasks.
 */
define('CONFIG_PHP', __DIR__ . '/config.php');

// 
// Use system configuration:
// 
$config = include(CONFIG_PHP);
$loader = include(CONFIG_SYS . "/loader.php");

// 
// Add namespace for tasks:
// 
$loader->registerNamespaces(
    array(
        'OpenExam\Console\Tasks' => APP_DIR . '/tasks'
    ), true
);
$loader->register();

// 
// Inject a limited set of system services:
// 
$ds = include(CONFIG_SYS . "/services.php");
$di = new Phalcon\DI\FactoryDefault\CLI();
foreach (array('config', 'dbread', 'dbwrite', 'dbaudit', 'catalog', 'logger', 'cache', 'audit', 'profiler', 'tr') as $service) {
        $di->set($service, $ds->get($service));
}

// 
// Use custom dispatcher:
// 
$di->set('dispatcher', function() use($di) {
        $dispatcher = new Phalcon\CLI\Dispatcher();
        $dispatcher->setDI($di);
        $dispatcher->setDefaultNamespace('OpenExam\Console\Tasks');
        $dispatcher->setDefaultTask('main');
        $dispatcher->setDefaultAction('index');
        return $dispatcher;
});

// 
// Use flash service specialized for console.
// 
$di->set('flash', function() {
        return new OpenExam\Library\Console\Flash();
});

// 
// Set authenticated user to task runner:
// 
if (extension_loaded('posix')) {
        $di->set('user', new OpenExam\Library\Security\User(
            posix_getpwuid(posix_geteuid())['name'], gethostname()
        ));
} else {
        $di->set('user', new OpenExam\Library\Security\User(
            get_current_user(), gethostname()
        ));
}
