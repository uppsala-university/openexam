<?php

$config1 = new \Phalcon\Config\Adapter\Ini(BASE_DIR . '/config/database.ini');
$config2 = new \Phalcon\Config(array(
        'database'    => array(
                'adapter'  => $config1->dbwrite->adapter,
                'host'     => $config1->dbwrite->host,
                'username' => $config1->dbwrite->username,
                'password' => $config1->dbwrite->password,
                'dbname'   => $config1->dbwrite->dbname,
        ),
        'application' => array(
                'controllersDir' => APP_DIR . '/controllers/',
                'modelsDir'      => APP_DIR . '/models/',
                'viewsDir'       => APP_DIR . '/views/',
                'pluginsDir'     => APP_DIR . '/plugins/',
                'libraryDir'     => APP_DIR . '/library/',
                'cacheDir'       => APP_DIR . '/cache/',
                'baseUri'        => '/openexam/',
        )
    ));

$config2->merge($config1);
return $config2;
