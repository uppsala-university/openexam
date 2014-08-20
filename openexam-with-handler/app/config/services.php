<?php

// 
// The source code is copyrighted, with equal shared rights, between the
// authors (see the file AUTHORS) and the OpenExam project, Uppsala University 
// unless otherwise explicit stated elsewhere.
// 
// File:    services.php
// Created: 2014-08-20 02:07:10
// 
// Author:  Anders Lövgren (QNET/BMC CompDept)
// 

/**
 * The FactoryDefault Dependency Injector automatically register the right services providing a full stack framework
 */
$di = new \Phalcon\DI\FactoryDefault();

/**
 * We register the events manager
 */
$di->set('dispatcher', function() use ($di) {

        $eventsManager = $di->getShared('eventsManager');

        $security = new Security($di);

        /**
         * We listen for events in the dispatcher using the Security plugin
         */
        $eventsManager->attach('dispatch', $security);

        $dispatcher = new Phalcon\Mvc\Dispatcher();
        $dispatcher->setEventsManager($eventsManager);

        return $dispatcher;
});

/**
 * The URL component is used to generate all kind of urls in the application
 */
$di->set('url', function() use ($config) {
        $url = new \Phalcon\Mvc\Url();
        $url->setBaseUri($config->application->baseUri);
        return $url;
});


$di->set('view', function() use ($config) {

        $view = new \Phalcon\Mvc\View();

        $view->setViewsDir($config->application->viewsDir);

        return $view;
});

/**
 * Database connection is created based in the parameters defined in the configuration file
 */
$di->set('dbread', function () use ($config) {
        return new DbAdapter(array(
                'adapter'  => $config->dbread->adapter,
                'host'     => $config->dbread->host,
                'username' => $config->dbread->username,
                'password' => $config->dbread->password,
                'dbname'   => $config->dbread->dbname
        ));
});
$di->set('dbwrite', function () use ($config) {
        return new DbAdapter(array(
                'adapter'  => $config->dbwrite->adapter,
                'host'     => $config->dbwrite->host,
                'username' => $config->dbwrite->username,
                'password' => $config->dbwrite->password,
                'dbname'   => $config->dbwrite->dbname
        ));
});

/**
 * For Apc?
 */
/*
  $di->set('modelsMetadata', function() use ($config) {
  if (isset($config->models->metadata)) {
  $metaDataConfig = $config->models->metadata;
  $metadataAdapter = 'Phalcon\Mvc\Model\Metadata\\'.$metaDataConfig->adapter;
  return new $metadataAdapter();
  }
  return new Phalcon\Mvc\Model\Metadata\Memory();
  });
 */

/**
 * Start the session the first time some component request the session service
 */
$di->set('session', function() {
        $session = new Phalcon\Session\Adapter\Files();
        $session->start();
        return $session;
});

/**
 * Register the flash service with custom CSS classes
 */
$di->set('flash', function() {
        return new Phalcon\Flash\Direct(array(
                'error'   => 'alert alert-error',
                'success' => 'alert alert-success',
                'notice'  => 'alert alert-info',
        ));
});

/**
 * Register a user component
 */
$di->set('elements', function() {
        return new Elements();
});

