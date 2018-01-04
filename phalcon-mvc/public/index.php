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
// Get application config from cache if existing.
// 
function getConfig($options)
{
        // 
        // Read config direct:
        // 
        if ($options['cached'] != 1) {
                return include(CONFIG_PHP);
        }

        // 
        // An instance unique cache key:
        // 
        $cachekey = basename(dirname(dirname(__DIR__)));

        // 
        // Create cache fontend:
        // 
        $frontend = new Phalcon\Cache\Frontend\Data(array(
                "lifetime" => 3600
            )
        );

        // 
        // Create cache backend:
        // 
        if ($options['method'] == "xcache") {
                $backend = new Phalcon\Cache\Backend\Xcache($frontend, array(
                        "prefix" => "site-config-"
                    )
                );
        }
        if ($options['method'] == "apc") {
                $backend = new Phalcon\Cache\Backend\Apc($frontend, array(
                        "prefix" => "site-config-"
                    )
                );
        }

        // 
        // Return direct if backend is unset:
        // 
        if (!isset($backend)) {
                return include(CONFIG_PHP);
        }

        // 
        // Fetch config from cache:
        // 
        if (!($config = $backend->get($cachekey))) {
                $config = include(CONFIG_PHP);
                $backend->save($cachekey, $config);
        }

        // 
        // Cleanup & return config:
        // 
        unset($cachekey);
        unset($frontend);
        unset($backend);

        return $config;
}

$config = getConfig(
    array(
            'cached' => getenv("OPENEXAM_CACHE_CONFIG"),
            'method' => getenv("OPENEXAM_CACHE_METHOD")
    )
);

if ($config->application->release) {
        error_reporting(E_ALL ^ E_NOTICE & ~E_DEPRECATED ^ E_STRICT);
        ini_set('display_errors', 0);
} else {
        error_reporting(E_ALL | E_STRICT);
        ini_set('display_errors', 1);
        ini_set('display_startup_errors', 1);
}

ini_set('session.gc_probability', 1);
ini_set('session.gc_divisor', 20000);
ini_set('session.gc_maxlifetime', $config->session->expires);
ini_set('session.cookie_lifetime', 0);

include CONFIG_SYS . "/loader.php";
include CONFIG_SYS . "/services.php";

$application = new \Phalcon\Mvc\Application($di);
echo $application->handle()->getContent();
