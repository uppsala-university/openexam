<?php

// 
// The source code is copyrighted, with equal shared rights, between the
// authors (see the file AUTHORS) and the OpenExam project, Uppsala University 
// unless otherwise explicit stated elsewhere.
// 
// File:    Postgresql.php
// Created: 2017-01-16 02:19:36
// 
// Author:  Anders Lövgren (QNET/BMC CompDept)
// 

namespace OpenExam\Library\Database\Adapter\Factory;

use OpenExam\Library\Database\Adapter\Deferred\Postgresql as PostgresqlAdapterDeferred;
use Phalcon\Config;
use Phalcon\Db\Adapter\Pdo\Postgresql as PostgresqlAdapterStandard;
use Phalcon\Db\AdapterInterface;

/**
 * PostgreSQL database adapter factory.
 *
 * @author Anders Lövgren (QNET/BMC CompDept)
 */
class Postgresql implements AdapterFactory
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
                        return new PostgresqlAdapterDeferred($config->toArray(), $params);
                } else {
                        return new PostgresqlAdapterStandard($config->toArray());
                }
        }

}
