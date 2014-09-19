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
 * System configuration directory.
 */
if (!defined('CONFIG_SYS')) {
        define('CONFIG_SYS', __DIR__);
}
/**
 * User settings directory.
 */
if (!defined('CONFIG_DIR')) {
        define('CONFIG_DIR', dirname(CONFIG_SYS));
}
/**
 * Application directory.
 */
if (!defined('APP_DIR')) {
        define('APP_DIR', dirname(CONFIG_DIR));
}
/**
 * Base directory (the Phalcon MVC app).
 */
if (!defined('BASE_DIR')) {
        define('BASE_DIR', dirname(APP_DIR));
}
/**
 * Project root directory.
 */
if (!defined('PROJ_DIR')) {
        define('PROJ_DIR', dirname(BASE_DIR));
}

/**
 * These are the system default settings. Site local configuration can 
 * be done in app/config/config.def.
 */
$config = new \Phalcon\Config(
    array(
        'application' => array(
                'controllersDir' => APP_DIR . '/controllers/',
                'modelsDir'      => APP_DIR . '/models/',
                'viewsDir'       => APP_DIR . '/views/',
                'pluginsDir'     => APP_DIR . '/plugins/',
                'libraryDir'     => APP_DIR . '/library/',
                'tasksDir'       => APP_DIR . '/tasks',
                'schemasDir'     => BASE_DIR . '/schemas/',
                'migrationsDir'  => BASE_DIR . '/schemas/migrations/',
                'cacheDir'       => BASE_DIR . '/cache/',
                'logsDir'        => BASE_DIR . '/logs',
                'baseDir'        => BASE_DIR . '/',
                'baseUri'        => '/phalcon-mvc/'
        ),
        'phpunit'     => array(
                'logging'  => true, // enable output logging
                'logfile'  => sys_get_temp_dir() . DIRECTORY_SEPARATOR . "phpunit-output.log",
                'truncate' => false, // truncate instead of rotating
                'rotate'   => true, // rotate logs
                'compress' => true, // compress rotated logs
                'maxsize'  => 10 * 1024 * 1024, // size limit in bytes (0 == no limit)
                'maxage'   => 3600 * 24  // max age in seconds (0 == no max age)
        )
    )
);

/**
 * Read the config protected configuration file:
 */
$config->merge(
    include(CONFIG_DIR . '/config.def')
);

/**
 * Merge user defined settings with system settings:
 */
$config->merge(array(
        'database' => array(
                'adapter'  => $config->dbwrite->adapter,
                'host'     => $config->dbwrite->host,
                'username' => $config->dbwrite->username,
                'password' => $config->dbwrite->password,
                'dbname'   => $config->dbwrite->dbname
        ),
));

return $config;
