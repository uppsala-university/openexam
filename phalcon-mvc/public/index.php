<?php

//BISMILLAH

try {
        define('CONFIG_PHP', __DIR__ . '/../app/config/system/config.php');

        $config = include(CONFIG_PHP);

        if ($config->application->release) {
                error_reporting(E_ALL ^ E_NOTICE);
        } else {
                error_reporting(E_ALL);
        }

        include CONFIG_SYS . "/loader.php";
        include CONFIG_SYS . "/services.php";

        $application = new \Phalcon\Mvc\Application($di);
        echo $application->handle()->getContent();
} catch (\Exception $e) {
        error_log($e);
        echo $e->getMessage();
}
