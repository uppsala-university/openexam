<?php

$dbconf = new \Phalcon\Config\Adapter\Ini(__DIR__ . '/../../config/database.ini');

return new \Phalcon\Config(array(
        'database'    => array(
                'adapter'  => $dbconf->dbwrite->adapter,
                'host'     => $dbconf->dbwrite->host,
                'username' => $dbconf->dbwrite->username,
                'password' => $dbconf->dbwrite->password,
                'dbname'   => $dbconf->dbwrite->dbname,
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
