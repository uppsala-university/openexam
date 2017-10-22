<?php

// 
// The source code is copyrighted, with equal shared rights, between the
// authors (see the file AUTHORS) and the OpenExam project, Uppsala University 
// unless otherwise explicit stated elsewhere.
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
