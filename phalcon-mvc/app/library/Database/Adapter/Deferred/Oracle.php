<?php

// 
// The source code is copyrighted, with equal shared rights, between the
// authors (see the file AUTHORS) and the OpenExam project, Uppsala University 
// unless otherwise explicit stated elsewhere.
// 
// File:    Oracle.php
// Created: 2017-01-10 02:12:39
// 
// Author:  Anders Lövgren (QNET/BMC CompDept)
// 

namespace OpenExam\Library\Database\Adapter\Deferred;

use OpenExam\Library\Database\Adapter\Factory\AdapterFactory;
use Phalcon\Config;
use Phalcon\Db\Adapter\Pdo\Oracle as OracleAdapter;
use Phalcon\Db\AdapterInterface;
use Phalcon\Db\Dialect\Oracle as OracleDialect;
use Phalcon\Db\DialectInterface;

/**
 * Caching Oracle database adapter with deferred connection.
 *
 * @author Anders Lövgren (QNET/BMC CompDept)
 */
class Oracle extends DeferredAdapter implements AdapterFactory
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
                $this->_dialect = new OracleDialect();
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
                return new OracleAdapter($config);
        }

        /**
         * Get adapter type.
         * @return string
         */
        public function getType()
        {
                return "oci";
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
