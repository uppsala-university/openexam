<?php

// 
// The source code is copyrighted, with equal shared rights, between the
// authors (see the file AUTHORS) and the OpenExam project, Uppsala University 
// unless otherwise explicit stated elsewhere.
// 
// File:    Sqlite.php
// Created: 2017-01-16 02:22:18
// 
// Author:  Anders Lövgren (QNET/BMC CompDept)
// 

namespace OpenExam\Library\Database\Adapter\Factory;

use OpenExam\Library\Database\Adapter\Deferred\Sqlite as SqliteAdapterDeferred;
use Phalcon\Config;
use Phalcon\Db\Adapter\Pdo\Sqlite as SqliteAdapterStandard;
use Phalcon\Db\AdapterInterface;

/**
 * SQLite database adapter factory.
 *
 * @author Anders Lövgren (QNET/BMC CompDept)
 */
class Sqlite implements AdapterFactory
{

        /**
         * Get database adapter.
         * 
         * @param Config $config The adapter config.
         * @param Config $params The connection parameters.
         * @return AdapterInterface 
         */
        public function createAdapter($config, $params = null)
        {
                if (isset($params)) {
                        return new SqliteAdapterDeferred($config->toArray(), $params);
                } else {
                        return new SqliteAdapterStandard($config->toArray());
                }
        }

}
