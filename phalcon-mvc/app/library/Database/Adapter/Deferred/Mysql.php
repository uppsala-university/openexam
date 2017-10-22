<?php

// 
// The source code is copyrighted, with equal shared rights, between the
// authors (see the file AUTHORS) and the OpenExam project, Uppsala University 
// unless otherwise explicit stated elsewhere.
// 
// File:    Mysql.php
// Created: 2017-01-10 02:10:35
// 
// Author:  Anders Lövgren (QNET/BMC CompDept)
// 

namespace OpenExam\Library\Database\Adapter\Deferred;

use OpenExam\Library\Database\Adapter\Factory\AdapterFactory;
use Phalcon\Config;
use Phalcon\Db\Adapter\Pdo\Mysql as MysqlAdapter;
use Phalcon\Db\AdapterInterface;
use Phalcon\Db\Dialect\MySQL as MysqlDialect;
use Phalcon\Db\DialectInterface;

/**
 * Caching MySQL database adapter with deferred connection.
 *
 * @author Anders Lövgren (QNET/BMC CompDept)
 */
class Mysql extends DeferredAdapter implements AdapterFactory
{

        /**
         * The database dialect.
         * @var DialectInterface 
         */
        private $_dialect;

        /**
         * Constructor.
         * @param array $config The adapter options.
         * @param Config $params The connection parameters.
         */
        public function __construct($config, $params)
        {
                parent::__construct($config, $params);
                $this->_dialect = new MysqlDialect();
        }

        /**
         * Get database adapter.
         * 
         * @param array $config The connection options.
         * @param array $params Other parameters (unused).
         * @return AdapterInterface
         */
        public function createAdapter($config, $params = null)
        {
                return new MysqlAdapter($config);
        }

        /**
         * Get adapter type.
         * @return string
         */
        public function getType()
        {
                return "mysql";
        }

        /**
         * Get database dialect.
         * @return DialectInterface
         */
        public function getDialect()
        {
                return $this->_dialect;
        }

}
