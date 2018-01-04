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
// File:    Direct.php
// Created: 2017-09-14 14:10:26
// 
// Author:  Anders Lövgren (Computing Department at BMC, Uppsala University)
// 

namespace OpenExam\Library\Database\Cache\Mediator;

use OpenExam\Library\Database\Exception as DatabaseException;
use Phalcon\Cache\BackendInterface;
use Phalcon\Db\AdapterInterface;

/**
 * The direct mediator.
 * 
 * Mediator handler that don't cache any queries. Using this mediator is 
 * effectively the same as disabling the database cache.
 *
 * @author Anders Lövgren (Computing Department at BMC, Uppsala University)
 */
class Direct extends MediatorHandler implements MediatorInterface
{

        /**
         * Constructor.
         * @param AdapterInterface $adapter The database adapter.
         * @param BackendInterface $cache The query cache.
         */
        public function __construct($adapter, $cache)
        {
                parent::__construct($adapter, null);
        }

        public function canCache()
        {
                return false;
        }

        public function exist($keyName)
        {
                return false;
        }

        public function fetch($keyName)
        {
                throw new DatabaseException("Calling fetch() on direct mediator is not supported");
        }

        public function setCache($cache)
        {
                // ignore
        }

}
