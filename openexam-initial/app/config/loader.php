<?php

$loader = new \Phalcon\Loader();

$loader->registerNamespaces(
    array(
            'OpenExam\Controllers' => $config->application->controllersDir,
            'OpenExam\Models'      => $config->application->modelsDir,
            'OpenExam'             => $config->application->libraryDir
    )
);
$loader->register();
