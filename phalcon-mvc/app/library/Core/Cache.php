<?php

/*
 * Copyright (C) 2015-2018 The OpenExam Project
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
// File:    Cache.php
// Created: 2015-04-02 00:30:02
// 
// Author:  Anders Lövgren (QNET/BMC CompDept)
// 

namespace OpenExam\Library\Core;

use OpenExam\Library\Core\Cache\Backend\Xcache as XcacheSubstitute;
use Phalcon\Cache\Backend\Apc as ApcCacheBackend;
use Phalcon\Cache\Backend\File as FileCacheBackend;
use Phalcon\Cache\Backend\Memcache as MemcacheBackend;
use Phalcon\Cache\Backend\Memory as MemoryBackend;
use Phalcon\Cache\Backend\Xcache as XcacheBackend;
use Phalcon\Cache\BackendInterface;
use Phalcon\Cache\Exception as CacheException;
use Phalcon\Cache\Frontend\Data as DataFrontend;
use Phalcon\Cache\Multiple;
use Phalcon\Config;
use Phalcon\Version as PhalconVersion;

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
                                'ultra'  => new DataFrontend(array(
                                        "lifetime" => $config->cache->lifetime->ultra
                                    )),
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

                        $options = $config->cache->backend->toArray();

                        // 
                        // Use application instance name to provide cache isolation
                        // by setting a unique prefix:
                        // 
                        if ($config->application->instance) {
                                foreach ($options as $type => $data) {
                                        if ($type != 'file') {
                                                $options[$type]['prefix'] = $config->application->instance . '-';
                                        } else {
                                                $options[$type]['cacheDir'] .= $config->application->instance . '/';
                                        }
                                }
                        }

                        if ($config->cache->enable->memory) {
                                $backends[] = new MemoryBackend(
                                    $frontend['ultra'], $options['memory']
                                );
                        }
                        if ($config->cache->enable->xcache && extension_loaded('xcache')) {
                                // 
                                // Use replacement class if framework version < 3.x as it 
                                // was broken causing memory exhaustion in 2.0.x
                                // 
                                if (PhalconVersion::getPart(PhalconVersion::VERSION_MAJOR) >= 3) {
                                        $backends[] = new XcacheBackend(
                                            $frontend['fast'], $options['xcache']
                                        );
                                } else {
                                        $backends[] = new XcacheSubstitute(
                                            $frontend['fast'], $options['xcache']
                                        );
                                }
                        }
                        if ($config->cache->enable->apc && extension_loaded('apc')) {
                                $backends[] = new ApcCacheBackend(
                                    $frontend['fast'], $options['apc']
                                );
                        }
                        if ($config->cache->enable->memcache && extension_loaded('memcache')) {
                                $backends[] = new MemcacheBackend(
                                    $frontend['medium'], $options['memcache']
                                );
                        }
                        if ($config->cache->enable->file) {
                                $backends[] = new FileCacheBackend(
                                    $frontend['slow'], $options['file']
                                );
                        }

                        if (!file_exists($options['file']['cacheDir'])) {
                                if (!mkdir($options['file']['cacheDir'], 0750, true)) {
                                        throw new CacheException(sprintf("Failed create cache directory %s", $options['file']['cacheDir']));
                                }
                        }

                        if (count($backends) != 0) {
                                $this->_fastest = $backends[0];
                        }

                        parent::__construct($backends);
                }
        }

        /**
         * Destructor.
         */
        public function __destruct()
        {
                unset($this->_fastest);
        }

        /**
         * Get fastest backend.
         * @return BackendInterface The cache backend.
         */
        public function getFastest()
        {
                return $this->_fastest;
        }

        /**
         * Get cache backends.
         * @return array
         */
        public function getBackends()
        {
                return $this->_backends;
        }

}
