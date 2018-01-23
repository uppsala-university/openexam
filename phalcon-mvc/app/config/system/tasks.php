<?php

/*
 * Copyright (C) 2014-2018 The OpenExam Project
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
 */

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
// Set error reporting:
// 
if ($config->application->release) {
        error_reporting(E_ALL ^ E_NOTICE & ~E_DEPRECATED ^ E_STRICT);
        ini_set('display_errors', 0);
} else {
        error_reporting(E_ALL | E_STRICT);
        ini_set('display_errors', 1);
        ini_set('display_startup_errors', 1);
}

// 
// Disable database caching:
// 
$config->dbread->params->adapter->cached = false;
$config->dbread->config->persistent = false;
$config->dbwrite->params->adapter->cached = false;
$config->dbwrite->config->persistent = false;

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
// Use system services for task:
// 
$di = include(CONFIG_SYS . "/services.php");

// 
// Replace some services specific for CLI:
// 
$dc = new Phalcon\DI\FactoryDefault\CLI();
$di->set('router', $dc->get('router'));

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
// Use flash service specialized for console:
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
