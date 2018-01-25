<?php

/*
 * Copyright (C) 2017-2018 The OpenExam Project
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
// File:    Backend.php
// Created: 2017-09-14 14:17:51
// 
// Author:  Anders Lövgren (Computing Department at BMC, Uppsala University)
// 

namespace OpenExam\Library\Database\Cache;

use OpenExam\Library\Core\Cache\Backend\Xcache as XcacheBackend;
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
