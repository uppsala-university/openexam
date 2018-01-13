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

// 
// File:    services.php
// Created: 2014-08-20 02:07:10
// 
// Author:  Anders Lövgren (QNET/BMC CompDept)
// 

/**
 * The FactoryDefault Dependency Injector automatically register the right services providing a full stack framework
 */
$di = new Phalcon\DI\FactoryDefault();

$di->set('config', $config, true);

$di->set('router', function() use($di) {
        return require CONFIG_SYS . '/routes.php';
}, true);

/**
 * Enforce model access check attaching to event manager. Model cache 
 * in ORM handled by custom model manager.
 */
$di->set('modelsManager', function() use($di, $config) {
        $eventsManager = new Phalcon\Events\Manager();
        $accessListener = new OpenExam\Plugins\Security\ModelAccessListener(
            function($resource) {
                $class = sprintf("OpenExam\Plugins\Security\Model\%sAccess", ucfirst($resource));
                if (class_exists($class)) {
                        return new $class();
                } else {
                        return false;
                }
        });
        $accessListener->setGrantLifetime($config->cache->lifetime->model);
        $accessListener->setEventsManager($eventsManager);
        $eventsManager->attach('model', $accessListener);
        $modelsManager = new OpenExam\Library\Model\ModelManager();
        $modelsManager->setEventsManager($eventsManager);
        return $modelsManager;
}, true);

/**
 * Setup meta data cache.
 */
$di->set('modelsMetadata', function() use($config) {
        if (!isset($config->application->release)) {
                $config->application->release = false;
        }
        if (!isset($config->metadata->type)) {
                foreach (array('xcache', 'apc') as $extension) {
                        if (extension_loaded($extension)) {
                                $config->metadata->type = $extension;
                                break;
                        }
                }
        }
        if (isset($config->application->instance)) {
                $config->metadata->prefix = $config->application->instance . '-';
        }
        if (!isset($config->metadata->type)) {
                return new Phalcon\Mvc\Model\Metadata\Memory();
        } elseif ($config->application->release == false) {
                return new Phalcon\Mvc\Model\Metadata\Memory();
        } elseif ($config->metadata->type == 'xcache') {
                return new Phalcon\Mvc\Model\Metadata\Xcache(
                    $config->metadata->toArray()
                );
        } elseif ($config->metadata->type == 'apc') {
                return new Phalcon\Mvc\Model\MetaData\Apc(
                    $config->metadata->toArray()
                );
        }
}, true);

/**
 * Setup the models cache service.
 */
$di->set('modelsCache', function() use($di, $config) {
        return new Phalcon\Cache\Backend\Memory(
            new Phalcon\Cache\Frontend\Data(
            array(
                "lifetime" => $config->cache->lifetime->model,
                "prefix"   => $config->application->instance
            )
        ));
}, true);

/**
 * The custom dispatcher enforcing auhentication.
 * See http://docs.phalconphp.com/en/latest/reference/tutorial-invo.html#events-management
 */
$di->set('dispatcher', function() use ($di) {
        $eventsManager = $di->getShared('eventsManager');
        $security = new OpenExam\Plugins\Security\DispatchListener();
        $eventsManager->attach('dispatch', $security);
        $eventsManager->attach('dispatch', function($event, $dispatcher) {
                if ($event->getType() == 'beforeDispatchLoop' && strstr($dispatcher->getActionName(), "-")) {
                        $dispatcher->setActionName(lcfirst(Phalcon\Text::camelize($dispatcher->getActionName())));
                }
        });
        $dispatcher = new Phalcon\Mvc\Dispatcher();
        $dispatcher->setEventsManager($eventsManager);
        return $dispatcher;
});

/**
 * The URL component is used to generate all kind of URL's in the application.
 */
$di->set('url', function() use ($config) {
        $url = new Phalcon\Mvc\Url();
        $url->setBaseUri($config->application->baseUri);
        return $url;
}, true);

/**
 * The view render service.
 */
$di->set('view', function() use ($config) {
        $eventsManager = new Phalcon\Events\Manager();
        $eventsManager->attach(
            'view', function (Phalcon\Events\Event $event, $view) {
                if ($event->getType() == 'beforeRender') {
                        $view->getDi()->get('user')->setPrimaryRole(null);
                }
        }
        );

        $view = new Phalcon\Mvc\View();
        $view->setEventsManager($eventsManager);
        $view->setViewsDir($config->application->viewsDir);

        return $view;
}, true);

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
 * Database connection is created based on the parameters defined in the configuration file.
 */
$di->set('dbread', function() use ($config, $di) {
        $factory = new OpenExam\Library\Database\Adapter\Factory();
        $factory->setConfig($config->dbread->config);
        $factory->setParams($config->dbread->params);
        $factory->setInstance($config->application->instance);
        $factory->setDI($di);
        return $factory->getAdapter();
}, true);
$di->set('dbwrite', function() use ($config, $di) {
        $factory = new OpenExam\Library\Database\Adapter\Factory();
        $factory->setConfig($config->dbwrite->config);
        $factory->setParams($config->dbwrite->params);
        $factory->setInstance($config->application->instance);
        $factory->setDI($di);
        return $factory->getAdapter();
}, true);
$di->set('dbaudit', function() use ($config, $di) {
        $factory = new OpenExam\Library\Database\Adapter\Factory();
        $factory->setConfig($config->dbaudit->config);
        $factory->setParams($config->dbaudit->params);
        $factory->setDI($di);
        return $factory->getAdapter();
}, true);
$di->set('dbcache', function() use($config) {
        $frontend = new Phalcon\Cache\Frontend\Data(array(
                'lifetime' => $config->dbcache->lifetime
        ));

        if ($config->dbcache->upper) {
                $options = $config->dbcache->upper->options->toArray();
                $options['prefix'] = $config->application->instance . '-' . $options['prefix'] . '-';
                $upper = OpenExam\Library\Database\Cache\Backend::create($config->dbcache->upper->backend, $frontend, $options);
        }

        if ($config->dbcache->lower) {
                $options = $config->dbcache->lower->options->toArray();
                $options['prefix'] = $config->application->instance . '-' . $options['prefix'] . '-';
                $lower = OpenExam\Library\Database\Cache\Backend::create($config->dbcache->lower->backend, $frontend, $options);
        }

        if (isset($upper) && isset($lower)) {
                $distributed = new OpenExam\Library\Database\Cache\Strategy\Distributed($frontend);
                $distributed->setUpperBackend($upper);
                $distributed->setLowerBackend($lower);
                return $distributed;
        } elseif (isset($upper)) {
                return $upper;
        } elseif (isset($lower)) {
                return $lower;
        }
}, true);

/**
 * The model audit service.
 */
$di->set('audit', function() use ($config) {
        return
            new OpenExam\Library\Model\Audit\Service(
            new OpenExam\Library\Model\Audit\Config\ServiceConfig($config->audit)
        );
}, true);

/**
 * Start the session the first time some component request the session service.
 */
$di->set('session', function() use ($config) {
        $session = new OpenExam\Library\Database\SessionAdapter($config->session);
        $session->start();
        return $session;
}, true);

/**
 * The cookies service.
 */
$di->set(
    'cookies', function () {
        $cookies = new Phalcon\Http\Response\Cookies();
        $cookies->useEncryption(false);
        return $cookies;
}, true);

/**
 * The locale service. Detect prefered locale on first use.
 */
$di->set('locale', function() use($config) {
        $locale = new OpenExam\Library\Globalization\Locale\Locale();
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
        return new OpenExam\Library\Globalization\Translate\Gettext\Translate('core');
}, true);

$di->set('acl', function() {
        return new OpenExam\Library\Security\Acl(
            require CONFIG_DIR . '/access.def'
        );
}, true);

$di->set('auth', function() use($config) {
        $auth = new OpenExam\Library\Security\Authentication(
            require CONFIG_DIR . '/auth.def'
        );
        $auth->setNormalizer('strtolower');
        return $auth;
}, true);

/**
 * The logged on user. Should be replaced by authentication.
 */
$di->set('user', function() use($config) {
        $user = new OpenExam\Library\Security\User();
        return $user;
}, true);

/**
 * Setup system logging (debug, system and auth)
 */
$di->set('logger', function() use($config, $di) {
        $logger = array();

        $formatter = new OpenExam\Library\Core\Formatter();
        $formatter->setDI($di);
        $formatter->setDateFormat('Y-m-d H:i:s');

        foreach ($config->logging as $name => $option) {
                if (!isset($option)) {
                        continue;
                } elseif (isset($option->file)) {
                        if (strpos($option->file, DIRECTORY_SEPARATOR) == false) {
                                $option->file = $config->application->logsDir . DIRECTORY_SEPARATOR . $option->file;
                        }
                        $logger[$name] = (
                            new Phalcon\Logger\Adapter\File($option->file)
                            )
                            ->setLogLevel($option->level)
                            ->setFormatter($formatter);
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
        $logger['phpunit'] = new Phalcon\Logger\Multiple();
        $logger['phpunit']->push($logger['debug']);
        $logger['phpunit']->push($logger['test']);
        return new Phalcon\Config($logger);
}, true);

/**
 * Get render service.
 */
$di->set('render', function() use($config) {
        return new OpenExam\Library\Render\RenderService();
}, true);

/**
 * Get catalog (directory information) service.
 */
$di->set('catalog', function() use($config, $di) {
        $manager = new OpenExam\Library\Catalog\DirectoryManager();
        $services = require CONFIG_DIR . '/catalog.def';
        foreach ($services as $name => $data) {
                $service = $data['service'];
                $domains = $data['domains'];
                $manager->register($service(), $domains, $name);
        }
        $manager->setDomain($config->user->domain);     // Set default domain.
        $manager->setCache(new OpenExam\Library\Catalog\DirectoryCache());
        return $manager;
}, true);

/**
 * The user attribute storage service. The storage part of catalog service.
 */
$di->set('attrstor', function() use($config) {
        $service = new OpenExam\Library\Catalog\Attribute\Storage();
        foreach ($config->attrstor as $method => $data) {
                if ($data['storage'] == 'database') {
                        $backend = new OpenExam\Library\Catalog\Attribute\Storage\Database($data['domains']);
                        $service->addBackend($method, $backend);
                }
        }
        return $service;
}, true);

/**
 * Capabilities service.
 */
$di->set('capabilities', function() {
        return new OpenExam\Library\Security\Capabilities(
            require CONFIG_DIR . '/access.def'
        );
}, true);

/**
 * The location information service.
 */
$di->set('location', function() {
        return new OpenExam\Library\Core\Location(
            require CONFIG_DIR . '/location.def'
        );
}, true);

/**
 * Multi-level backend binary cache.
 */
$di->set('cache', function() use($config) {
        return new OpenExam\Library\Core\Cache\Backend\KeyValueStore(
            new OpenExam\Library\Core\Cache($config)
        );
}, true);

/**
 * The system performance profiler.
 */
$di->set('profiler', function() use ($config) {
        return new OpenExam\Library\Monitor\Performance\Profiler($config->profile);
}, true);

/**
 * The web browser polyfill service.
 */
$di->set('polyfill', function() {
        return new OpenExam\Library\Gui\Browser\Polyfill();
}, true);

return $di;
