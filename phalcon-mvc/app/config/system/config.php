<?php

use Phalcon\Config;
use Phalcon\Logger;

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

if (!defined('CONFIG_SYS')) {
        /**
         * System configuration directory.
         */
        define('CONFIG_SYS', __DIR__);
}
if (!defined('CONFIG_DIR')) {
        /**
         * User settings directory.
         */
        define('CONFIG_DIR', dirname(CONFIG_SYS));
}
if (!defined('APP_DIR')) {
        /**
         * Application directory.
         */
        define('APP_DIR', dirname(CONFIG_DIR));
}
if (!defined('BASE_DIR')) {
        /**
         * Base directory (the Phalcon MVC app).
         */
        define('BASE_DIR', dirname(APP_DIR));
}
if (!defined('PROJ_DIR')) {
        /**
         * Project root directory.
         */
        define('PROJ_DIR', dirname(BASE_DIR));
}
if (!defined('EXTERN_DIR')) {
        /**
         * External source directory.
         */
        define('EXTERN_DIR', APP_DIR . '/extern/');
}

/**
 * These are the system default settings. Site local configuration can 
 * be done in app/config/config.def.
 */
$config = new Config(
    array(
        'application' => array(
                /**
                 * The application layout:
                 */
                'controllersDir' => APP_DIR . '/controllers/',
                'modelsDir'      => APP_DIR . '/models/',
                'viewsDir'       => APP_DIR . '/views/',
                'pluginsDir'     => APP_DIR . '/plugins/',
                'libraryDir'     => APP_DIR . '/library/',
                'tasksDir'       => APP_DIR . '/tasks',
                'schemasDir'     => BASE_DIR . '/schemas/',
                'migrationsDir'  => BASE_DIR . '/schemas/migrations/',
                'localeDir'      => BASE_DIR . '/locale/',
                'cacheDir'       => BASE_DIR . '/cache/',
                'auditDir'       => BASE_DIR . '/audit',
                'logsDir'        => BASE_DIR . '/logs',
                'baseDir'        => BASE_DIR . '/',
                'docsDir'        => PROJ_DIR . '/docs',
                /**
                 * These should always be user defined:
                 */
                'instance'       => null,
                'release'        => true,
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
        ),
        /**
         * System locale settings.
         */
        'locale'      => array(
                'request' => 'lang', // Detect from request parameter 'lang'
                'default' => 'sv_SE.UTF-8' // Default locale if not detected.
        ),
        /**
         * Settings for GNU gettext (setup only).
         */
        'gettext'     => array(
                'program' => array(
                        'xgettext' => '/usr/bin/xgettext',
                        'msgmerge' => '/usr/bin/msgmerge',
                        'msgfmt'   => '/usr/bin/msgfmt',
                        'msgcat'   => '/usr/bin/msgcat',
                        'msgconv'  => '/usr/bin/msgconv',
                        'msginit'  => '/usr/bin/msginit'
                ),
                'options' => array(
                        'xgettext' => '--language=PHP --package-name="@name@" --package-version="@version@" --msgid-bugs-address="@address@" --copyright-holder="@copying@" --add-comments=\'//{tr}\' --from-code=UTF-8 --no-wrap --output=@template@',
                        'msgmerge' => '--no-wrap --update',
                        'msgfmt'   => '--strict --statistics --check --output-file=@output@',
                        'msgcat'   => '--strict --no-wrap --to-code=UTF-8 --output-file=@output@',
                        'msgconv'  => '--no-wrap',
                        'msginit'  => '--no-wrap --input=@template@ --output=@output@ --locale=@locale@ --no-translator'
                ),
                'package' => array(
                        'name'    => 'openexam-php',
                        'version' => '2.0',
                        'copying' => 'The authors, the Computing Department at BMC and the OpenExam project (Uppsala University)',
                        'address' => 'support@bmc.uu.se'
                )
        ),
        /**
         * Define translation modules. See docs/develop/gettext.txt
         */
        'translate'   => array(
                'core' => array(
                        'directories' => array(
                                'phalcon-mvc/app/config',
                                'phalcon-mvc/app/controllers',
                                'phalcon-mvc/app/library',
                                'phalcon-mvc/app/models',
                                'phalcon-mvc/app/plugins'
                        )
                ),
//                'tasks'   => array(
//                        'directories' => array('phalcon-mvc/app/tasks'),
//                        'merge'       => array('core')
//                ),
//                'web'     => array(
//                        'directories' => array(
//                                'phalcon-mvc/app/views/auth',
//                                'phalcon-mvc/app/views/index',
//                                'phalcon-mvc/app/views/public',
//                                'phalcon-mvc/app/views/layouts'
//                        ),
//                        'files'       => array(
//                                'phalcon-mvc/app/views/index.phtml'
//                        )
//                ),
//                'admin'   => array(
//                        'directories' => array('phalcon-mvc/app/views/admin'),
//                        'merge'       => array('core', 'web')
//                ),
//                'student' => array(
//                        'directories' => array('phalcon-mvc/app/views/student'),
//                        'merge'       => array('core', 'web')
//                ),
//                'manager' => array(
//                        'directories' => array('phalcon-mvc/app/views/staff'),
//                        'merge'       => array('core', 'web')
//                )
        ),
        /**
         * System logging to file and syslog(3). See README-LOGGER.
         */
        'logging'     => array(
                'debug'  => array(
                        'file'  => 'debug.log',
                        'level' => Logger::DEBUG
                ),
                'system' => array(
                        'syslog'   => 'openexam',
                        'level'    => Logger::NOTICE,
                        'option'   => LOG_NDELAY,
                        'facility' => LOG_LOCAL0
                ),
                'access' => array(
                        'file'  => 'access.log',
                        'level' => Logger::INFO
                ),
                'cache'  => array(
                        'file'  => 'cache.log',
                        'level' => Logger::INFO
                ),
                'auth'   => array(
                        'syslog'   => 'openexam',
                        'level'    => Logger::INFO,
                        'option'   => LOG_NDELAY,
                        'facility' => LOG_AUTH
                ),
                'test'   => array(
                        'file'  => 'phpunit.log',
                        'level' => Logger::DEBUG
                )
        ),
        /**
         * Configuration for the render service.
         * See http://wkhtmltopdf.org/libwkhtmltox/pagesettings.html
         */
        'render'      => array(
                'image' => array(
                        'fmt'          => 'png',
                        'imageQuality' => 95,
                // 'load.cookieJar' => BASE_DIR . '/cache/cookies.jar'
                ),
                'pdf'   => array(
                        'produceForms' => false,
                        'outline'      => true,
                        'outlineDepth' => 2,
                // 'load.cookieJar' => BASE_DIR . '/cache/cookies.jar'
                ),
                // 
                // Security token, an absolute file path or string:
                // 
                'token' => BASE_DIR . '/cache/render.sec'
        ),
        /**
         * Models meta data cache. Only activated if application->release is
         * true. Fallback on default memory cache during development.
         */
        'metadata'    => array(
                'prefix'   => 'openexam',
                'lifetime' => 86400
        ),
        /**
         * Session configuration.
         */
        'session'     => array(
                'expires'   => 21600, // Expires after (seconds)
                'refresh'   => 7200, // Refresh threshold (seconds)
                'cleanup'   => true, // Enable garbage collection
                'startPage' => 'exam/index'     // Start page (user initiated)
        ),
        /**
         * Multi level object cache.
         */
        'cache'       => array(
                /**
                 * Enable these extensions (if available):
                 */
                'enable'   => array(
                        'xcache'   => true,
                        'apc'      => true,
                        'memcache' => true,
                        'file'     => false
                ),
                /**
                 * Cached object lifetime:
                 */
                'lifetime' => array(
                        'fast'   => 3600,
                        'medium' => 86400,
                        'slow'   => 604800,
                        'model'  => 1800
                ),
                /**
                 * Extension specific settings:
                 */
                'backend'  => array(
                        'xcache'   => array(
                                'prefix' => ''
                        ),
                        'apc'      => array(
                                'prefix' => ''
                        ),
                        'memcache' => array(
                                'prefix' => '',
                                'host'   => 'localhost',
                                'port'   => '11211'
                        ),
                        'file'     => array(
                                'prefix'   => '',
                                'cacheDir' => BASE_DIR . '/cache/app/'
                        )
                )
        ),
        /*
         * System performance profiler config. 
         */
        'profile' => false,
        /**
         * Audit is disabled by default.
         */
        'audit'   => false,
        /**
         * Performance monitoring is disabled by default.
         */
        'monitor' => false
    )
);

/**
 * Read the config protected configuration file:
 */
$config->merge(new Config(
    include(CONFIG_DIR . '/config.def')
));

/**
 * Merge user defined settings with system settings:
 */
$config->merge(new Config(
    array(
        'database' => array(
                'adapter'  => $config->dbwrite->adapter,
                'host'     => $config->dbwrite->host,
                'username' => $config->dbwrite->username,
                'password' => $config->dbwrite->password,
                'dbname'   => $config->dbwrite->dbname
        )
    )
));

return $config;
