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

/**
 * Enforce model access check using custom model manager.
 */
$di->set('modelsManager', function() use($di) {
        $eventsManager = new \Phalcon\Events\Manager();
        $accessListener = new OpenExam\Plugins\Security\ModelAccessListener(
            function($resource) {
                $class = sprintf("OpenExam\Plugins\Security\Model\%sAccess", ucfirst($resource));
                if (class_exists($class)) {
                        return new $class();
                } else {
                        return false;
                }
        });
        $accessListener->setEventsManager($eventsManager);
        $eventsManager->attach('model', $accessListener);
        $modelsManager = new \Phalcon\Mvc\Model\Manager();
        $modelsManager->setEventsManager($eventsManager);
        return $modelsManager;
}, true);

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
                'sv_SE.UTF-8' => _('Swedish'),
                'en_US'       => _('English (US)'),
                'en_GB'       => _('English (GB)'),
                'C'           => _('Browser Default')
        ));
        $locale->detect($config->locale->request, $config->locale->default, true);
        return $locale;
}, true);

/**
 * Translation service for application core. Views should typical use their
 * own translator object (with its own message catalogs).
 */
$di->set('tr', function() use($config) {
        return new \OpenExam\Library\Globalization\Translate\Gettext\Translate('core');
});

$di->set('acl', function() {
        return new \OpenExam\Library\Security\Acl(
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

/**
 * Setup system logging (debug, system and auth)
 */
$di->set('logger', function() use($config) {
        $logger = array();
        foreach ($config->logging as $name => $option) {
                if (!isset($option)) {
                        continue;
                } elseif (isset($option->file)) {
                        if (strpos($option->file, DIRECTORY_SEPARATOR) == false) {
                                $option->file = $config->application->logsDir . DIRECTORY_SEPARATOR . $option->file;
                        }
                        $logger[$name] = (
                            new Phalcon\Logger\Adapter\File($option->file)
                            )->setLogLevel($option->level);
                } elseif (isset($option->syslog)) {
                        $logger[$name] = (
                            new Phalcon\Logger\Adapter\Syslog($option->syslog, $option)
                            )->setLogLevel($option->level);
                } elseif (isset($option->stream)) {
                        $logger[$name] = (
                            new Phalcon\Logger\Adapter\Stream($option->stream, $option)
                            )->setLogLevel($option->level);
                } elseif (isset($option->database)) {
                        $logger[$name] = (
                            new Phalcon\Logger\Adapter\Database($option->database, $option)
                            )->setLogLevel($option->level);
                } elseif (isset($option->firelogger)) {
                        $logger[$name] = (
                            new Phalcon\Logger\Adapter\Firelogger($option->firelogger, $option)
                            )->setLogLevel($option->level);
                } elseif (isset($option->firephp)) {
                        $logger[$name] = (
                            new Phalcon\Logger\Adapter\Firephp()
                            )->setLogLevel($option->level);
                }
        }
        $logger['phpunit'] = new \Phalcon\Logger\Multiple();
        $logger['phpunit']->push($logger['debug']);
        $logger['phpunit']->push($logger['test']);
        return new \Phalcon\Config($logger);
}, true);

return $di;
