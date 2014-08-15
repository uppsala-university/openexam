<?php

use Phalcon\DI\FactoryDefault;
use Phalcon\Mvc\View;
use Phalcon\Mvc\Url as UrlResolver;
use Phalcon\Db\Adapter\Pdo\Mysql as DbAdapter;
use Phalcon\Mvc\Model\Metadata\Memory as MetaDataAdapter;
use Phalcon\Session\Adapter\Files as SessionAdapter;
use Phalcon\Mvc\Dispatcher;

/**
 * The FactoryDefault Dependency Injector automatically register the right services providing a full stack framework
 */
$di = new FactoryDefault();

/**
 * The URL component is used to generate all kind of urls in the application
 */
$di->set('url', function () use ($config) {
        $url = new UrlResolver();
        $url->setBaseUri($config->application->baseUri);

        return $url;
}, true);

/**
 * Setting up the view component
 */
$di->set('view', function () use ($config) {

        $view = new View();
        $view->setViewsDir($config->application->viewsDir);

        return $view;
}, true);

/**
 * Database connection is created based in the parameters defined in the configuration file
 */
$di->set('dbread', function () use ($config) {
        return new DbAdapter(array(
                'host'     => $config->dbread->host,
                'username' => $config->dbread->username,
                'password' => $config->dbread->password,
                'dbname'   => $config->dbread->dbname
        ));
});
$di->set('dbwrite', function () use ($config) {
        return new DbAdapter(array(
                'host'     => $config->dbwrite->host,
                'username' => $config->dbwrite->username,
                'password' => $config->dbwrite->password,
                'dbname'   => $config->dbwrite->dbname
        ));
});

/**
 * If the configuration specify the use of metadata adapter use it or use memory otherwise
 */
$di->set('modelsMetadata', function () {
        return new MetaDataAdapter();
});

/**
 * Dispatcher use a default namespace
 */
$di->set('dispatcher', function () {
        $dispatcher = new Dispatcher();
        $dispatcher->setDefaultNamespace('OpenExam\Controllers');
        return $dispatcher;
});

/**
 * Start the session the first time some component request the session service
 */
$di->set('session', function () {
        $session = new SessionAdapter();
        $session->start();

        return $session;
});
