<?php

// 
// The source code is copyrighted, with equal shared rights, between the
// authors (see the file AUTHORS) and the OpenExam project, Uppsala University 
// unless otherwise explicit stated elsewhere.
// 
// File:    DatabaseAdapter.php
// Created: 2014-08-25 07:27:15
// 
// Author:  Anders Lövgren (QNET/BMC CompDept)
// 

namespace OpenExam\Library\Database;

use Phalcon\Config;
use Phalcon\Db\Adapter\Pdo as PdoAdapter;
use Phalcon\Db\Adapter\Pdo\Mysql as MysqlAdapter;
use Phalcon\Db\Adapter\Pdo\Oracle as OracleAdapter;
use Phalcon\Db\Adapter\Pdo\Postgresql as PostgresqlAdapter;
use Phalcon\Db\Adapter\Pdo\Sqlite as SqliteAdapter;

/**
 * Database adapter factory.
 *
 * @author Anders Lövgren (QNET/BMC CompDept)
 */
class Adapter
{

        /**
         * Default number of connect attempts.
         */
        const CONNECT_RETRY = 5;
        /**
         * Default seconds between connect attempts.
         */
        const CONNECT_SLEEP = 2;
        /**
         * MySQL adapter identifier.
         */
        const MYSQL = 'Mysql';
        /**
         * PostgreSQL adapter identifier.
         */
        const POSTGRE = 'Postgresql';
        /**
         * Oracle adapter identifier.
         */
        const ORACLE = 'Oracle';
        /**
         * SQLite adapter identifier.
         */
        const SQLITE = 'Sqlite';

        /**
         * Create database adapter.
         * 
         * It's possible to force connection to be established during creation
         * by passing 'connect' and 'retry' as config options:
         * 
         * <code>
         * 'dbread' => array(
         *      'connect' => true,      // connect on create
         *      'retry'   => 5,         // number of attempts
         *      'sleep'   => 2          // wait between attempts
         * )
         * </code>
         * 
         * @param Config $config The adapter config.
         * @return PdoAdapter
         */
        public static function create($config)
        {
                // 
                // First option is to use adapter:
                // 
                if (isset($config->adapter)) {
                        if ($config->adapter == self::MYSQL) {
                                return self::open($config, self::MYSQL);
                        } elseif ($config->adapter == self::ORACLE) {
                                return self::open($config, self::ORACLE);
                        } elseif ($config->adapter == self::POSTGRE) {
                                return self::open($config, self::POSTGRE);
                        } elseif ($config->adapter == self::SQLITE) {
                                return self::open($config, self::SQLITE);
                        }
                }

                //  
                // Second option is to use DSN:
                // 
                if (isset($config->dsn)) {
                        if (strstr($config->dsn, 'mysql:')) {
                                return self::open($config, self::MYSQL);
                        } elseif (strstr($config->dsn, 'oci:')) {
                                return self::open($config, self::ORACLE);
                        } elseif (strstr($config->dsn, 'pgsql:')) {
                                return self::open($config, self::POSTGRE);
                        } elseif (strstr($config->dsn, 'sqlite:')) {
                                return self::open($config, self::SQLITE);
                        }
                }

                // 
                // No usable adapter found:
                // 
                throw new Exception("Unsupported database type");
        }

        /**
         * Open data adapter.
         * 
         * Default behavior is to open database adapter and fail direct
         * on any errors unless connect options are set.
         * 
         * @param Config $config The adapter config.
         * @param string $type The adapter type.
         * @return PdoAdapter
         */
        private static function open($config, $type)
        {
                //
                // Set defaults:
                // 
                if ($config->connect) {
                        if (!$config->retry) {
                                $config->retry = self::CONNECT_RETRY;
                        }
                        if (!$config->sleep) {
                                $config->sleep = self::CONNECT_SLEEP;
                        }
                } else {
                        if (!$config->retry) {
                                $config->retry = 0;
                        }
                        if (!$config->sleep) {
                                $config->sleep = 0;
                        }
                }

                // 
                // Try to establish database connection:
                // 
                if ($config->connect) {
                        return self::connect($config, self::setup($config, $type));
                } else {
                        return self::setup($config, $type);
                }
        }

        /**
         * Setup database adapter.
         * 
         * @param Config $config The adapter config.
         * @param string $type The adapter type.
         * @return PdoAdapter
         */
        private static function setup($config, $type)
        {
                while (true) {
                        try {
                                switch ($type) {
                                        case self::MYSQL:
                                                return new MysqlAdapter($config->toArray());
                                        case self::ORACLE:
                                                return new OracleAdapter($config->toArray());
                                        case self::POSTGRE:
                                                return new PostgresqlAdapter($config->toArray());
                                        case self::SQLITE:
                                                return new SqliteAdapter($config->toArray());
                                }
                        } catch (\Exception $exception) {
                                if ($config->retry == 0) {
                                        throw $exception;
                                } else {
                                        $config->retry--;
                                        sleep($config->sleep);
                                }
                        }
                }
        }

        /**
         * Connect adapter to database.
         * 
         * @param Config $config The adapter config.
         * @param PdoAdapter $adapter The PDO database adapter.
         */
        private static function connect($config, $adapter)
        {
                while (true) {
                        if (($adapter->connect() !== false)) {
                                return $adapter;
                        } elseif ($config->retry == 0) {
                                throw new \Exception("Failed establish database connection");
                        } else {
                                $config->retry--;
                        }
                }
        }

}
