<?php

//BISMILLAH

try {
        define('CONFIG_PHP', __DIR__ . '/../app/config/system/config.php');

        $config = include(CONFIG_PHP);

        if ($config->application->release) {
                error_reporting(E_ALL ^ E_NOTICE & ~E_DEPRECATED);
                ini_set('display_errors', 0);
        } else {
                error_reporting(E_ALL | E_STRICT);
                ini_set('display_errors', 1);
                ini_set('display_startup_errors', 1);
        }

        include CONFIG_SYS . "/loader.php";
        include CONFIG_SYS . "/services.php";

        $application = new \Phalcon\Mvc\Application($di);
        echo $application->handle()->getContent();
} catch (\Exception $e) {
        error_log($e);
        echo $e->getMessage();
}
