<?php

// 
// The source code is copyrighted, with equal shared rights, between the
// authors (see the file AUTHORS) and the OpenExam project, Uppsala University 
// unless otherwise explicit stated elsewhere.
// 
// File:    DataTarget.php
// Created: 2016-04-15 15:59:30
// 
// Author:  Anders Lövgren (Computing Department at BMC, Uppsala University)
// 

namespace OpenExam\Library\Model\Audit\Target;

use OpenExam\Library\Database\Exception as DatabaseException;
use OpenExam\Library\Model\Audit\Target\AuditTarget;
use Phalcon\Mvc\ModelInterface;
use Phalcon\Mvc\User\Component;

/**
 * Database target for audit trails.
 * 
 * @author Anders Lövgren (Computing Department at BMC, Uppsala University)
 */
class DataTarget extends Component implements AuditTarget
{

        /**
         * Default database connection name.
         */
        const CONNECTION_NAME = 'dbaudit';
        /**
         * Default database table name.
         */
        const TABLE_NAME = 'audit';

        /**
         * Target options.
         * @var array 
         */
        private $_options;

        /**
         * Constructor.
         * @param array $options The target options.
         * @param ModelInterface $model The target model.
         */
        public function __construct($options, $model)
        {
                if (!isset($options['connection'])) {
                        $options['connection'] = self::CONNECTION_NAME;
                }
                if (!isset($options['table'])) {
                        $options['table'] = $model->getResourceName();
                }

                $this->_options = $options;
        }

        /**
         * Write changes to database.
         * 
         * @param array $changes The model changes.
         * @return int 
         */
        public function write($changes)
        {
                $changes['changes'] = serialize($changes['changes']);

                if ($this->getDI()->has('dbaudit')) {
                        if (!($dbh = $this->getDI()->get('dbaudit'))) {
                                throw new DatabaseException("Failed get audit database connection");
                        }

                        $tbl = $this->_options['table'];
                        $sql = "INSERT INTO $tbl(type, res, rid, user, time, changes) VALUES(?,?,?,?,?,?)";
                        $sth = $dbh->prepare($sql);
                        $res = $sth->execute(array_values($changes));

                        return $res;
                }
        }

}
