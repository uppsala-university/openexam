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
// File:    DatabaseAdapter.php
// Created: 2014-08-25 07:27:15
// 
// Author:  Anders Lövgren (QNET/BMC CompDept)
// 

namespace OpenExam\Library\Database\Adapter;

use OpenExam\Library\Database\Adapter\Factory\AdapterFactory;
use OpenExam\Library\Database\Cache\Backend;
use OpenExam\Library\Database\Cache\Mediator;
use OpenExam\Library\Database\Exception;
use Phalcon\Cache\BackendInterface;
use Phalcon\Cache\Frontend\Data as DataFrontend;
use Phalcon\Config;
use Phalcon\Db\AdapterInterface;
use Phalcon\Di\InjectionAwareInterface;
use Phalcon\DiInterface;

/**
 * Database adapter factory.
 *
 * @author Anders Lövgren (QNET/BMC CompDept)
 */
class Factory implements InjectionAwareInterface
{

        /**
         * Default number of connect attempts.
         */
        const CONNECT_RETRY = 5;
        /**
         * Default seconds between connect attempts.
         */
        const CONNECT_SLEEP = 2;
        /**
         * Use cached adapter.
         */
        const ADAPTER_CACHED = false;
        /**
         * Use deferred connection adapter.
         */
        const ADAPTER_DEFERRED = false;
        /**
         * Default caching backend.
         */
        const CACHE_BACKEND = 'dbcache';
        /**
         * Default cache lifetime.
         */
        const CACHE_LIFETIME = 7200;
        /**
         * Default mediator handler.
         */
        const MEDIATOR_TYPE = 'mutable';
        /**
         * MySQL adapter identifier.
         */
        const MYSQL = 'Mysql';
        /**
         * PostgreSQL adapter identifier.
         */
        const POSTGRE = 'Postgresql';
        /**
         * Oracle adapter identifier.
         */
        const ORACLE = 'Oracle';
        /**
         * SQLite adapter identifier.
         */
        const SQLITE = 'Sqlite';

        /**
         * The adapter config.
         * @var Config 
         */
        private $_config;
        /**
         * The connection parameters.
         * @var Config 
         */
        private $_params;
        /**
         * Cache instance.
         * @var string 
         */
        private $_instance;
        /**
         * The cache backend.
         * @var BackendInterface 
         */
        private $_cache;
        /**
         * @var DiInterface
         */
        private $_di;

        /**
         * Constructor.
         * @param Config $config The adapter config.
         * @param Config $params The connection parameters.
         */
        public function __construct($config = null, $params = null)
        {
                $this->_config = $config;
                $this->_params = $params;
        }

        /**
         * Set adapter config.
         * @param Config $config The adapter config.
         */
        public function setConfig($config)
        {
                $this->_config = $config;
        }

        /**
         * Set connection parameters.
         * @param Config $params The connection parameters.
         */
        public function setParams($params)
        {
                $this->_params = $params;
        }

        /**
         * Set cache instance.
         * @param string $instance The cache instance.
         */
        public function setInstance($instance)
        {
                $this->_instance = $instance;
        }

        /**
         * Set cache backend.
         * @param BackendInterface $cache The cache backend.
         */
        public function setCache($cache)
        {
                $this->_cache = $cache;
        }

        /**
         * Check if cache is defined.
         * @return boolean
         */
        public function hasCache()
        {
                return isset($this->_cache);
        }

        /**
         * Get cache backend.
         * @return BackendInterface
         */
        public function getCache()
        {
                return $this->_cache;
        }

        /**
         * Get database adapter.
         * @return Mediator
         * @throws Exception
         */
        public function getAdapter()
        {
                // 
                // Set connect, adapter and cache defaults:
                // 
                $this->setDefaults();

                // 
                // Get adapter factory:
                // 
                $factory = $this->getFactory();

                // 
                // Use params section:
                // 
                $config = $this->_params;

                // 
                // Try to establish database connection with retry. If using
                // deferred adapter, then no connection is established here.
                // 
                while (true) {
                        try {
                                // 
                                // Create deferred database adapter if requested:
                                // 
                                if ($config->adapter->deferred) {
                                        $adapter = $factory->createAdapter($this->_config, $config->connect);
                                } else {
                                        $adapter = $factory->createAdapter($this->_config);
                                }

                                // 
                                // Use adapter/cache mediator if requested:
                                // 
                                if ($config->adapter->cached) {
                                        return $this->getMediator($adapter);
                                } else {
                                        return $adapter;
                                }
                        } catch (\Exception $exception) {
                                if (--$config->connect->retry < 0) {
                                        throw $exception;
                                } else {
                                        sleep($config->connect->sleep);
                                }
                        }
                }
        }

        /**
         * Get adapter factory.
         * @return AdapterFactory
         * @throws Exception
         */
        public function getFactory()
        {
                // 
                // Use config section:
                // 
                $config = $this->_config;

                // 
                // First option is to use adapter:
                // 
                if (isset($config->adapter)) {
                        if ($config->adapter == self::MYSQL) {
                                return new Factory\Mysql();
                        } elseif ($config->adapter == self::ORACLE) {
                                return new Factory\Oracle();
                        } elseif ($config->adapter == self::POSTGRE) {
                                return new Factory\Postgresql();
                        } elseif ($config->adapter == self::SQLITE) {
                                return new Factory\Sqlite();
                        }
                }

                //  
                // Second option is to use DSN:
                // 
                if (isset($config->dsn)) {
                        if (strstr($config->dsn, 'mysql:')) {
                                return new Factory\Mysql();
                        } elseif (strstr($config->dsn, 'oci:')) {
                                return new Factory\Oracle();
                        } elseif (strstr($config->dsn, 'pgsql:')) {
                                return new Factory\Postgresql();
                        } elseif (strstr($config->dsn, 'sqlite:')) {
                                return new Factory\Sqlite();
                        }
                }

                // 
                // No usable adapter found:
                // 
                throw new Exception("Unsupported database type");
        }

        public function getDI()
        {
                return $this->_di;
        }

        public function setDI(DiInterface $dependencyInjector)
        {
                $this->_di = $dependencyInjector;
        }

        /**
         * Set default config.
         */
        private function setDefaults()
        {
                // 
                // Use params section:
                // 
                $config = $this->_params;

                //  
                // Set connection defaults:
                // 
                if (!isset($config->connect)) {
                        $config->connect = new Config();
                }
                if (!isset($config->connect->retry)) {
                        $config->connect->retry = self::CONNECT_RETRY;
                }
                if (!isset($config->connect->sleep)) {
                        $config->connect->sleep = self::CONNECT_SLEEP;
                }

                // 
                // Set adapter defaults:
                // 
                if (!isset($config->adapter)) {
                        $config->adapter = new Config();
                }
                if (!isset($config->adapter->cached)) {
                        $config->adapter->cached = self::ADAPTER_CACHED;
                }
                if (!isset($config->adapter->deferred)) {
                        $config->adapter->deferred = self::ADAPTER_DEFERRED;
                }

                // 
                // Set caching defaults:
                // 
                if (!isset($config->cache)) {
                        $config->cache = new Config();
                }
                if (!isset($config->cache->mediator)) {
                        $config->cache->mediator = self::MEDIATOR_TYPE;
                }
                if (!isset($config->cache->backend)) {
                        $config->cache->backend = self::CACHE_BACKEND;
                }
                if (!isset($config->cache->lifetime)) {
                        $config->cache->lifetime = self::CACHE_LIFETIME;
                }
                if (!isset($config->cache->options)) {
                        $config->cache->options = new Config();
                }
        }

        /**
         * Set cache backend.
         */
        private function setBackend()
        {
                // 
                // Use params section:
                // 
                $config = $this->_params;

                // 
                // Use dbcache service:
                // 
                if ($this->_di->has($config->cache->backend)) {
                        $this->_cache = $this->_di->get($config->cache->backend);
                        return;
                }

                // 
                // Use user defined callback:
                // 
                if (is_callable($config->cache->backend)) {
                        $this->_cache = call_user_func($config->cache->backend);
                        return;
                }

                // 
                // Use backend factory function:
                // 
                $options = $config->cache->options->toArray();
                $options['prefix'] = $this->_instance . '-' . $options['prefix'] . '-';

                $frontend = new DataFrontend(array(
                        'lifetime' => $config->cache->lifetime
                ));

                $this->_cache = Backend::create($config->cache->backend, $frontend, $options);
        }

        /**
         * Create cache as needed and return.
         * @return BackendInterface
         */
        private function useCache()
        {
                if (!$this->hasCache()) {
                        $this->setBackend();
                }
                if ($this->hasCache()) {
                        return $this->getCache();
                }
        }

        /**
         * Get cache mediator.
         * 
         * @param AdapterInterface $adapter The database adapter.
         * @return Mediator
         */
        private function getMediator($adapter)
        {
                // 
                // Use cache section:
                // 
                $config = $this->_params->cache;

                // 
                // Get database cache:
                // 
                $cache = $this->useCache();

                // 
                // Get mediator handler:
                // 
                $handler = $this->getHandler($config->mediator, $adapter, $cache);

                // 
                // Set special cache options:
                // 
                if (isset($config->coherence)) {
                        $cache->setCoherence($config->coherence->toArray());
                }

                // 
                // Create cache mediator using prefered cache:
                // 
                $mediator = new Mediator($handler);

                // 
                // Set mediator handler options:
                // 
                if (isset($config->exclude)) {
                        $handler->setFilter($config->exclude->toArray(), $config->exclude->merge);
                }
                if (isset($config->limits->min)) {
                        $handler->setMinimun($config->limits->min);
                }
                if (isset($config->limits->max)) {
                        $mediator->setMaximun($config->limits->max);
                }

                return $mediator;
        }

        /**
         * Create mediator handler.
         * 
         * @param string $type The mediator name.
         * @param PhalconDb\AdapterInterface $adapter The database adapter.
         * @param BackendInterface $cache The cache object.
         * @return Mediator\MediatorHandler
         */
        private function getHandler($type, $adapter, $cache)
        {
                return Mediator::create($type, $adapter, $cache);
        }

}
