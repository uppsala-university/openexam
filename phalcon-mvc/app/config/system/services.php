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

$di->set('config', $config, true);

$di->set('router', function() use($di) {
        return require CONFIG_SYS . '/routes.php';
});

//$di->set('dispatcher', function () {
//
//        $dispatcher = new Phalcon\Mvc\Dispatcher();
//
//        // $dispatcher->setDefaultNamespace('OpenExam\Controllers\Core');
//
//        return $dispatcher;
//});

/**
 * We register the events manager
 */
//$di->set('dispatcher', function() use ($di) {
//
//        $eventsManager = $di->getShared('eventsManager');
//
//        $security = new Security($di);
//
//        /**
//         * We listen for events in the dispatcher using the Security plugin
//         */
//        $eventsManager->attach('dispatch', $security);
//
//        $dispatcher = new Phalcon\Mvc\Dispatcher();
//        $dispatcher->setEventsManager($eventsManager);
//
//        return $dispatcher;
//});

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
 * Register the flash service with custom CSS classes
 */
$di->set('flash', function() {
        return new \Phalcon\Flash\Direct(array(
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

/**
 * Database connection is created based in the parameters defined in the configuration file
 */
$di->set('dbread', function () use ($config) {
        return \OpenExam\Library\Database\Adapter::create($config->dbread);
});
$di->set('dbwrite', function () use ($config) {
        return \OpenExam\Library\Database\Adapter::create($config->dbwrite);
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
        $session = new \Phalcon\Session\Adapter\Files();
        $session->start();
        return $session;
}, true);

/**
 * The locale service. Detect prefered locale on first use.
 */
$di->set('locale', function() use($config) {
        $locale = new \OpenExam\Library\Globalization\Locale\Locale();
        $locale->setLocales(array(
                'sv_SE' => _('Swedish'),
                'en_US' => _('English (US)'),
                'en_GB' => _('English (GB)'),
                'C'     => _('Browser Default')
        ));
        $locale->detect($config->locale->request, $config->locale->default);
        return $locale;
}, true);

$di->set('acl', function() {
        return new \OpenExam\Plugins\Security\Acl(
            require CONFIG_DIR . '/access.def'
        );
});

$di->set('auth', function() {
        return new \OpenExam\Library\Security\Authentication(
            require CONFIG_DIR . '/auth.def'
        );
}, true);

/**
 * The logged on user. Should be replaced by authentication.
 */
$di->set('user', function() use($config) {
        $user = new \OpenExam\Library\Security\User();
        return $user;
}, true);

return $di;
