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
// File:    Simple.php
// Created: 2017-09-14 14:09:42
// 
// Author:  Anders Lövgren (Computing Department at BMC, Uppsala University)
// 

namespace OpenExam\Library\Database\Cache\Mediator;

use OpenExam\Library\Database\Cache\Backend\Native as NativeCache;
use Phalcon\Cache\BackendInterface;
use Phalcon\Db\AdapterInterface;

/**
 * The simple mediator.
 * 
 * This mediator handler caches query results for a small period of time,
 * commonly known as micro-caching. TTL of cache entry is determined from
 * the cache frontend, but can also be explicit set.
 * 
 * The simplest mediator handler with greatest performance. Require client
 * applications prepared for handle storage on their side. Of course, this
 * depends on TTL being used too.
 * 
 * @author Anders Lövgren (Computing Department at BMC, Uppsala University)
 */
class Simple extends MediatorHandler implements MediatorInterface
{

        /**
         * Constructor.
         * @param AdapterInterface $adapter The database adapter.
         * @param BackendInterface $cache The query cache.
         */
        public function __construct($adapter, $cache)
        {
                parent::__construct($adapter, new NativeCache($cache));
        }

        public function setCache($cache)
        {
                $this->_cache = new NativeCache($cache);
        }

        /**
         * Set lifetime
         * @param int $ttl The TTL for cache entries.
         */
        public function setLifetime($ttl)
        {
                $this->_cache->setLifetime($ttl);
        }

}
