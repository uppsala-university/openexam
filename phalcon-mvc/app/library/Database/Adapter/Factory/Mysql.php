<?php

// 
// The source code is copyrighted, with equal shared rights, between the
// authors (see the file AUTHORS) and the OpenExam project, Uppsala University 
// unless otherwise explicit stated elsewhere.
// 
// File:    Mysql.php
// Created: 2017-01-16 02:13:16
// 
// Author:  Anders Lövgren (QNET/BMC CompDept)
// 

namespace OpenExam\Library\Database\Adapter\Factory;

use OpenExam\Library\Database\Adapter\Deferred\Mysql as MysqlAdapterDeferred;
use Phalcon\Config;
use Phalcon\Db\Adapter\Pdo\Mysql as MysqlAdapterStandard;
use Phalcon\Db\AdapterInterface;

/**
 * MySQL database adapter factory.
 *
 * @author Anders Lövgren (QNET/BMC CompDept)
 */
class Mysql implements AdapterFactory
{

        /**
         * Get database adapter.
         * 
         * @param Config $config The adapter configuration.
         * @param Config $params The connection parameters.
         * @return AdapterInterface 
         */
        public function createAdapter($config, $params = null)
        {
                if (isset($params)) {
                        return new MysqlAdapterDeferred($config->toArray(), $params);
                } else {
                        return new MysqlAdapterStandard($config->toArray());
                }
        }

}
