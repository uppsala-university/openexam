<?php

// 
// The source code is copyrighted, with equal shared rights, between the
// authors (see the file AUTHORS) and the OpenExam project, Uppsala University 
// unless otherwise explicit stated elsewhere.
// 
// File:    Backend.php
// Created: 2017-09-14 14:17:51
// 
// Author:  Anders Lövgren (Computing Department at BMC, Uppsala University)
// 

namespace OpenExam\Library\Database\Cache;

use OpenExam\Library\Database\Exception;
use Phalcon\Cache\Backend\Aerospike as AerospikeBackend;
use Phalcon\Cache\Backend\Apc as ApcBackend;
use Phalcon\Cache\Backend\Database as DatabaseBackend;
use Phalcon\Cache\Backend\File as FileBackend;
use Phalcon\Cache\Backend\Libmemcached as LibMemcachedBackend;
use Phalcon\Cache\Backend\Memcache as MemcacheBackend;
use Phalcon\Cache\Backend\Mongo as MongoBackend;
use Phalcon\Cache\Backend\Redis as RedisBackend;
use Phalcon\Cache\BackendInterface;
use Phalcon\Cache\FrontendInterface;

/**
 * The cache backend factory.
 *
 * @author Anders Lövgren (Computing Department at BMC, Uppsala University)
 */
class Backend
{

        /**
         * Create cache backend.
         * 
         * @param string $type The cache type.
         * @param FrontendInterface $frontend The cache frontend.
         * @param array $options Backend options.
         * 
         * @return BackendInterface
         * @throws Exception
         */
        public static function create($type, $frontend, $options)
        {
                switch ($type) {
                        case 'xcache':
                                return new XcacheBackend($frontend, $options);
                        case 'apc':
                                return new ApcBackend($frontend, $options);
                        case 'redis':
                                return new RedisBackend($frontend, $options);
                        case 'memcache':
                                return new MemcacheBackend($frontend, $options);
                        case 'libmemcached':
                                return new LibMemcachedBackend($frontend, $options);
                        case 'mongo':
                                return new MongoBackend($frontend, $options);
                        case 'aerospike':
                                return new AerospikeBackend($frontend, $options);
                        case 'database':
                                return new DatabaseBackend($frontend, $options);
                        case 'file':
                                return new FileBackend($frontend, $options);
                        default:
                                throw new Exception("Unsupported cache backend $type");
                }
        }

}
