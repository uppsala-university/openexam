<?php

// 
// The source code is copyrighted, with equal shared rights, between the
// authors (see the file AUTHORS) and the OpenExam project, Uppsala University 
// unless otherwise explicit stated elsewhere.
// 
// File:    AdapterFactory.php
// Created: 2017-01-16 02:35:09
// 
// Author:  Anders Lövgren (QNET/BMC CompDept)
// 

namespace OpenExam\Library\Database\Adapter\Factory;

use Phalcon\Config;
use Phalcon\Db\AdapterInterface;

/**
 * Database adapter factory.
 * @author Anders Lövgren (QNET/BMC CompDept)
 */
interface AdapterFactory
{

        /**
         * Get database adapter.
         * 
         * @param Config $config The adapter configuration.
         * @param Config $params The connection parameters.
         * @return AdapterInterface 
         */
        function createAdapter($config, $params = null);
}
