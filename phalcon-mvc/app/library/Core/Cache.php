<?php

// 
// The source code is copyrighted, with equal shared rights, between the
// authors (see the file AUTHORS) and the OpenExam project, Uppsala University 
// unless otherwise explicit stated elsewhere.
// 
// File:    Cache.php
// Created: 2015-04-02 00:30:02
// 
// Author:  Anders Lövgren (QNET/BMC CompDept)
// 

namespace OpenExam\Library\Core;

use Phalcon\Cache\Backend\Apc as ApcCache;
use Phalcon\Cache\Backend\File as FileCache;
use Phalcon\Cache\Backend\Memcache as MemcacheCache;
use Phalcon\Cache\Backend\Xcache;
use Phalcon\Cache\BackendInterface;
use Phalcon\Cache\Frontend\Data as DataFrontend;
use Phalcon\Cache\Multiple;
use Phalcon\Config;

/**
 * Cache service configurator.
 * 
 * This class should not be used direct. Instead it should be created inside 
 * a service, returning the wrapped multi level cache object.
 * 
 * The multi-level cache facilates saving same data in several cache locations 
 * with different lifetimes, reading first from the one with the faster adapter 
 * and ending with the slowest one until the data has expired.
 * 
 * Use application->instance for cache isolation between multiple application 
 * instances running on the same server. Another options is to override the
 * default prefix name per cache backend.
 * 
 * @author Anders Lövgren (QNET/BMC CompDept)
 */
class Cache
{

        /**
         * Fastest cache backend.
         * @var BackendInterface 
         */
        private $_fastest;
        /**
         * Multi level cache.
         * @var Multiple 
         */
        private $_multiple;

        /**
         * Constructor.
         * @param Config $config
         * @param array $backends
         */
        public function __construct($config, $backends = array())
        {
                if ($config->cache) {
                        $frontend = array(
                                'fast'   => new DataFrontend(array(
                                        "lifetime" => $config->cache->lifetime->fast
                                    )),
                                'medium' => new DataFrontend(array(
                                        "lifetime" => $config->cache->lifetime->medium
                                    )),
                                'slow'   => new DataFrontend(array(
                                        "lifetime" => $config->cache->lifetime->slow
                                    ))
                        );

                        $options = $config->cache->toArray();

                        // 
                        // Use application instance name to provide cache isolation
                        // by setting a unique prefix:
                        // 
                        if ($config->application->instance) {
                                foreach ($options as $type => $data) {
                                        if (isset($data['prefix'])) {
                                                $options[$type]['prefix'] = $config->application->instance . '-';
                                        }
                                }
                        }
                        if ($config->cache->enable->file) {
                                $options['file']['cacheDir'] .= $config->application->instance;
                        }

                        if ($config->cache->enable->xcache && extension_loaded('xcache')) {
                                $backends[] = new Xcache(
                                    $frontend['fast'], $options['xcache']
                                );
                        }
                        if ($config->cache->enable->apc && extension_loaded('apc')) {
                                $backends[] = new ApcCache(
                                    $frontend['fast'], $options['apc']
                                );
                        }
                        if ($config->cache->enable->memcache && extension_loaded('memcache')) {
                                $backends[] = new MemcacheCache(
                                    $frontend['medium'], $options['memcache']
                                );
                        }
                        if ($config->cache->enable->file) {
                                $backends[] = new FileCache(
                                    $frontend['slow'], $options['file']
                                );
                        }

                        if (!file_exists($options['file']['cacheDir'])) {
                                mkdir($options['file']['cacheDir']);
                        }

                        if (count($backends) != 0) {
                                $this->_fastest = $backends[0];
                        }

                        $this->_multiple = new Multiple($backends);
                }
        }

        /**
         * Get multi level cache object.
         * @return Multiple
         */
        public function getInstance()
        {
                return $this->_multiple;
        }

        /**
         * Get fastest backend.
         * @return BackendInterface The cache backend.
         */
        public function getFastest()
        {
                return $this->_fastest;
        }

}
