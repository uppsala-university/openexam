<?php

$config1 = new \Phalcon\Config\Adapter\Ini(__DIR__ . '/../../config/database.ini');
$config2 = new \Phalcon\Config(array(
        'database'    => array(
                'adapter'  => $config1->dbwrite->adapter,
                'host'     => $config1->dbwrite->host,
                'username' => $config1->dbwrite->username,
                'password' => $config1->dbwrite->password,
                'dbname'   => $config1->dbwrite->dbname,
        ),
        'application' => array(
                'controllersDir' => __DIR__ . '/../../app/controllers/',
                'modelsDir'      => __DIR__ . '/../../app/models/',
                'viewsDir'       => __DIR__ . '/../../app/views/',
                'pluginsDir'     => __DIR__ . '/../../app/plugins/',
                'libraryDir'     => __DIR__ . '/../../app/library/',
                'cacheDir'       => __DIR__ . '/../../app/cache/',
                'baseUri'        => '/openexam/',
        )
    ));

$config2->merge($config1);
return $config2;
