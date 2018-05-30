<?php
define('PROJ_DIR', realpath(dirname(__DIR__)));
define('BASE_DIR', PROJ_DIR . '/phalcon-mvc');
define('APP_DIR', BASE_DIR . '/app');
define('CONFIG_DIR', APP_DIR . '/config');
define('CONFIG_SYS', CONFIG_DIR . '/system');
define('EXTERN_DIR', APP_DIR . '/extern/');
define('CONFIG_PHP', CONFIG_SYS . '/config.php');
