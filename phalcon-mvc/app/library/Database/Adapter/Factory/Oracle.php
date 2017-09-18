<?php

// 
// The source code is copyrighted, with equal shared rights, between the
// authors (see the file AUTHORS) and the OpenExam project, Uppsala University 
// unless otherwise explicit stated elsewhere.
// 
// File:    Oracle.php
// Created: 2017-01-16 02:22:01
// 
// Author:  Anders Lövgren (QNET/BMC CompDept)
// 

namespace OpenExam\Library\Database\Adapter\Factory;

use OpenExam\Library\Database\Adapter\Deferred\Oracle as OracleAdapterDeferred;
use Phalcon\Config;
use Phalcon\Db\Adapter\Pdo\Oracle as OracleAdapterStandard;
use Phalcon\Db\AdapterInterface;

/**
 * Oracle database adapter factory.
 *
 * @author Anders Lövgren (QNET/BMC CompDept)
 */
class Oracle implements AdapterFactory
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
                        return new OracleAdapterDeferred($config->toArray(), $params);
                } else {
                        return new OracleAdapterStandard($config->toArray());
                }
        }

}
