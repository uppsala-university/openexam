<?php

$loader = new \Phalcon\Loader();

/**
 * We're a registering a set of directories taken from the configuration file
 */
$loader->registerDirs(
    array(
            $config->application->controllersDir,
            $config->application->modelsDir
    )
);

$loader->registerNamespaces(
    array(
            'OpenExam' => $config->application->libraryDir
    )
);
$loader->register();
