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
 * Cache service class.
 * 
 * Provides multi-level cache saving the same data in several cache locations 
 * with different lifetimes, reading first from the one with the faster adapter 
 * and ending with the slowest one until the data expires.
 * 
 * @author Anders Lövgren (QNET/BMC CompDept)
 */
class Cache extends Multiple
{

        /**
         * Fastest cache backend.
         * @var BackendInterface 
         */
        private $_fastest;

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

                        if ($config->cache->enable->xcache && extension_loaded('xcache')) {
                                $backends[] = new Xcache(
                                    $frontend['fast'], $config->cache->xcache->toArray()
                                );
                        }
                        if ($config->cache->enable->apc && extension_loaded('apc')) {
                                $backends[] = new ApcCache(
                                    $frontend['fast'], $config->cache->apc->toArray()
                                );
                        }
                        if ($config->cache->enable->memcache && extension_loaded('memcache')) {
                                $backends[] = new MemcacheCache(
                                    $frontend['medium'], $config->cache->memcache->toArray()
                                );
                        }
                        if ($config->cache->enable->file) {
                                $backends[] = new FileCache(
                                    $frontend['slow'], $config->cache->file->toArray()
                                );
                        }

                        if (!file_exists($config->cache->file->cacheDir)) {
                                mkdir($config->cache->file->cacheDir);
                        }
                        if (count($backends) != 0) {
                                $this->_fastest = $backends[0];
                        }
                }

                parent::__construct($backends);
        }

        /**
         * Get cache backends.
         * @return BackendInterface[]
         */
        public function getBackends()
        {
                return $this->_backends;
        }

        /**
         * Returns a cached content reading the internal backends.
         * 
         * @param string $keyName The cache key.
         * @param long $lifetime The cache entry lifetime.
         * @return mixed
         */
        public function get($keyName, $lifetime = null)
        {
                // 
                // Cache might be disabled or having dynamic added backends.
                // 
                if (!isset($this->_fastest)) {
                        if (count($this->_backends) == 0) {
                                return false;
                        } else {
                                $this->_fastest = $this->_backends[0];
                        }
                }

                // 
                // Always use fastest backend when possible.
                // 
                if ($this->_fastest->exists($keyName, $lifetime)) {
                        return $this->_fastest->get($keyName, $lifetime);
                }

                // 
                // See if any backend contains key.
                // 
                if (!parent::exists($keyName, $lifetime)) {
                        return false;
                }

                // 
                // Insert from slower backend into fastest.
                // 
                $content = parent::get($keyName, $lifetime);
                $this->_fastest->save($keyName, $content, $lifetime);

                return $content;
        }

        /**
         * Stores cached content into all backends and stops the frontend.
         * 
         * If current memory usage is close to the limit, then the entry is discarded
         * from caching. This prevents process memory exhausted fatal error in cache
         * intensive routines.
         * 
         * @param string $keyName The cache key.
         * @param string $content Description
         * @param long $lifetime The cache entry lifetime.
         * @param boolean $stopBuffer
         */
        public function save($keyName = null, $content = null, $lifetime = null, $stopBuffer = null)
        {
                $usage = memory_get_usage();
                $limit = substr(ini_get('memory_limit'), 0, -1) * 1024 * 1024;

                if ($usage < $limit) {
                        parent::save($keyName, $content, $lifetime, $stopBuffer);
                }
        }

}
