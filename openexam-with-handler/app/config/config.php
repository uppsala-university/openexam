<?php

// 
// The source code is copyrighted, with equal shared rights, between the
// authors (see the file AUTHORS) and the OpenExam project, Uppsala University 
// unless otherwise explicit stated elsewhere.
// 
// File:    config.php
// Created: 2014-08-20 01:44:49
// 
// Author:  Anders Lövgren (QNET/BMC CompDept)
// 

/**
 * Define some useful constants:
 */
define('BASE_DIR', realpath(__DIR__ . '/../..'));
define('APP_DIR', BASE_DIR . '/app');

/**
 * Read the config protected configuration file:
 */
$config = new Phalcon\Config\Adapter\Ini(APP_DIR . '/config/config.ini');
$config->merge(array(
        'database'    => array(
                'adapter'  => $config->dbread->adapter,
                'host'     => $config->dbread->host,
                'username' => $config->dbread->username,
                'password' => $config->dbread->password,
                'dbname'   => $config->dbread->dbname
        ),
        'application' => array(
                'controllersDir' => APP_DIR . '/controllers',
                'modelsDir'      => APP_DIR . '/models',
                'viewsDir'       => APP_DIR . '/views',
                'pluginsDir'     => APP_DIR . '/plugins',
                'libraryDir'     => APP_DIR . '/library',
                'baseUri'        => '/openexam/'
        )
));

return $config;
