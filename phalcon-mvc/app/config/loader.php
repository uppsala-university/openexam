<?php

// 
// The source code is copyrighted, with equal shared rights, between the
// authors (see the file AUTHORS) and the OpenExam project, Uppsala University 
// unless otherwise explicit stated elsewhere.
// 
// File:    loader.php
// Created: 2014-08-20 01:56:59
// 
// Author:  Anders Lövgren (QNET/BMC CompDept)
// 

$loader = new \Phalcon\Loader();

$loader->registerNamespaces(
    array(
            'OpenExam\Controllers' => $config->application->controllersDir,
            'OpenExam\Models'      => $config->application->modelsDir,
            'OpenExam\Library'     => $config->application->libraryDir,
            'OpenExam\Plugins'     => $config->application->pluginsDir,
    )
);

/**
 * We're a registering a set of directories taken from the configuration file
 */
$loader->registerDirs(
    array(
            $config->application->controllersDir,
            $config->application->pluginsDir,
            $config->application->libraryDir,
            $config->application->modelsDir,
    )
);

$loader->register();

/**
 * Include Composer auto-loader.
 */
require_once BASE_DIR . '/../vendor/autoload.php';
