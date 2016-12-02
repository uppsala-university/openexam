<?php

/**
 * System configuration directory.
 */
define('CONFIG_SYS', realpath(__DIR__ . '/../app/config/system'));

/**
 * User settings directory.
 */
define('CONFIG_DIR', dirname(CONFIG_SYS));

/**
 * Application directory.
 */
define('APP_DIR', dirname(CONFIG_DIR));

/**
 * Base directory (the Phalcon MVC app).
 */
define('BASE_DIR', dirname(APP_DIR));

/**
 * Project root directory.
 */
define('PROJ_DIR', dirname(BASE_DIR));

/**
 * External source directory.
 */
define('EXTERN_DIR', APP_DIR . '/extern/');

/**
 * The bootstrap script called by Phalcon.
 */
define('CONFIG_PHP', CONFIG_SYS . '/config.php');

// 
// Keep system configuration in cache:
// 
$frontend = new Phalcon\Cache\Frontend\Data(array(
        "lifetime" => 3600
    )
);
$backend = new Phalcon\Cache\Backend\Xcache($frontend, array(
        "prefix" => "config-data"
    )
);
if (!($config = $backend->get('site-config'))) {
        $config = include(CONFIG_PHP);
        $backend->save('site-config', $config);
}

if ($config->application->release) {
        error_reporting(E_ALL ^ E_NOTICE & ~E_DEPRECATED);
        ini_set('display_errors', 0);
} else {
        error_reporting(E_ALL | E_STRICT);
        ini_set('display_errors', 1);
        ini_set('display_startup_errors', 1);
}

ini_set('session.gc_probability', 1);
ini_set('session.gc_divisor', 1000);
ini_set('session.gc_maxlifetime', $config->session->expires);
ini_set('session.cookie_lifetime', 0);

include CONFIG_SYS . "/loader.php";
include CONFIG_SYS . "/services.php";

$application = new \Phalcon\Mvc\Application($di);
echo $application->handle()->getContent();
