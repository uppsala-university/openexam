<?php

// 
// The source code is copyrighted, with equal shared rights, between the
// authors (see the file AUTHORS) and the OpenExam project, Uppsala University 
// unless otherwise explicit stated elsewhere.
// 
// File:    DatabaseAdapter.php
// Created: 2014-08-25 07:27:15
// 
// Author:  Anders Lövgren (QNET/BMC CompDept)
// 

namespace OpenExam\Library\Database\Adapter;

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
                $this->setDefaults();

                $factory = $this->getFactory();

                while (true) {
                        try {
                                if ($this->_params->adapter->deferred) {
                                        $adapter = $factory->createAdapter($this->_config, $this->_params->connect);
                                } else {
                                        $adapter = $factory->createAdapter($this->_config);
                                }

                                if ($this->_params->adapter->cached) {
                                        return $this->getMediator($adapter);
                                } else {
                                        return $adapter;
                                }
                        } catch (\Exception $exception) {
                                if (--$this->_params->connect->retry < 0) {
                                        throw $exception;
                                } else {
                                        sleep($this->_params->connect->sleep);
                                }
                        }
                }
        }

        /**
         * Get adapter factory.
         * @return Factory\AdapterFactory
         * @throws Exception
         */
        public function getFactory()
        {
                // 
                // First option is to use adapter:
                // 
                if (isset($this->_config->adapter)) {
                        if ($this->_config->adapter == self::MYSQL) {
                                return new Factory\Mysql();
                        } elseif ($this->_config->adapter == self::ORACLE) {
                                return new Factory\Oracle();
                        } elseif ($this->_config->adapter == self::POSTGRE) {
                                return new Factory\Postgresql();
                        } elseif ($this->_config->adapter == self::SQLITE) {
                                return new Factory\Sqlite();
                        }
                }

                //  
                // Second option is to use DSN:
                // 
                if (isset($this->_config->dsn)) {
                        if (strstr($this->_config->dsn, 'mysql:')) {
                                return new Factory\Mysql();
                        } elseif (strstr($this->_config->dsn, 'oci:')) {
                                return new Factory\Oracle();
                        } elseif (strstr($this->_config->dsn, 'pgsql:')) {
                                return new Factory\Postgresql();
                        } elseif (strstr($this->_config->dsn, 'sqlite:')) {
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
                // Set connection defaults:
                // 
                if (!$this->_params->connect) {
                        $this->_params->connect = new Config();
                }
                if (!$this->_params->connect->retry) {
                        $this->_params->connect->retry = self::CONNECT_RETRY;
                }
                if (!$this->_params->connect->sleep) {
                        $this->_params->connect->sleep = self::CONNECT_SLEEP;
                }

                // 
                // Set adapter defaults:
                // 
                if (!$this->_params->adapter) {
                        $this->_params->adapter = new Config();
                }
                if (!$this->_params->adapter->cached) {
                        $this->_params->adapter->cached = self::ADAPTER_CACHED;
                }
                if (!$this->_params->adapter->deferred) {
                        $this->_params->adapter->deferred = self::ADAPTER_DEFERRED;
                }

                // 
                // Set caching defaults:
                // 
                if (!$this->_params->cache) {
                        $this->_params->cache = new Config();
                }
                if (!$this->_params->cache->backend) {
                        $this->_params->cache->backend = self::CACHE_BACKEND;
                }
                if (!$this->_params->cache->lifetime) {
                        $this->_params->cache->lifetime = self::CACHE_LIFETIME;
                }
                if (!$this->_params->cache->options) {
                        $this->_params->cache->options = new Config();
                }
        }

        /**
         * Set cache backend.
         */
        private function setBackend()
        {
                // 
                // Use dbcache service:
                // 
                if ($this->_di->has($this->_params->cache->backend)) {
                        $this->_cache = $this->_di->get($this->_params->cache->backend);
                        return;
                }

                // 
                // Use user defined callback:
                // 
                if (is_callable($this->_params->cache->backend)) {
                        $this->_cache = call_user_func($this->_params->cache->backend);
                        return;
                }

                // 
                // Use backend factory function:
                // 
                $options = $this->_params->cache->options->toArray();
                $options['prefix'] = $this->_instance . '-' . $options['prefix'] . '-';

                $frontend = new DataFrontend(array(
                        'lifetime' => $this->_params->cache->lifetime
                ));

                $this->_cache = Backend::create($this->_params->cache->backend, $frontend, $options);
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
                $mediator = new Mediator($adapter);
                $mediator->setCache($this->useCache());

                if ($this->_params->cache->exclude) {
                        $mediator->setFilter($this->_params->cache->exclude->toArray());
                }
                if ($this->_params->cache->limits->min) {
                        $mediator->setMinimun($this->_params->cache->limits->min);
                }
                if ($this->_params->cache->limits->max) {
                        $mediator->setMaximun($this->_params->cache->limits->max);
                }

                return $mediator;
        }

}
