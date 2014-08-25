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

namespace OpenExam\Library\Core;

/**
 * Database adapter factory.
 *
 * @author Anders Lövgren (QNET/BMC CompDept)
 */
class DatabaseAdapter
{

        /**
         * Create Phalcon PDO database adapter object.
         * 
         * @param array $config
         * @return \Phalcon\Db\Adapter\Pdo
         */
        public static function create($config)
        {
                if (isset($config['adapter'])) {
                        switch ($config['adapter']) {
                                case 'Mysql':
                                        return new \Phalcon\Db\Adapter\Pdo\Mysql((array) $config);
                                case 'Psotgresql':
                                        return new \Phalcon\Db\Adapter\Pdo\Postgresql((array) $config);
                                case 'Oracle':
                                        return new \Phalcon\Db\Adapter\Pdo\Oracle((array) $config);
                                case 'Sqlite':
                                        return new \Phalcon\Db\Adapter\Pdo\Sqlite((array) $config);
                        }
                } elseif (isset($config['dsn'])) {
                        if (strstr($config['dsn'], 'mysql:')) {
                                return new \Phalcon\Db\Adapter\Pdo\Mysql((array) $config);
                        } elseif (strstr($config['dsn'], 'pgsql:')) {
                                return new \Phalcon\Db\Adapter\Pdo\Postgresql((array) $config);
                        } elseif (strstr($config['dsn'], 'oci:')) {
                                return new \Phalcon\Db\Adapter\Pdo\Oracle((array) $config);
                        } elseif (strstr($config['dsn'], 'sqlite:')) {
                                return new \Phalcon\Db\Adapter\Pdo\Sqlite((array) $config);
                        }
                } else {
                        throw new \Phalcon\Db\Exception("Unsupported database type.");
                }
        }

}
