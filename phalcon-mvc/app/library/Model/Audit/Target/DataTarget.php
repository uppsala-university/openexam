<?php

/*
 * Copyright (C) 2016-2018 The OpenExam Project
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
 */

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
                if ($this->getDI()->has('dbaudit')) {
                        if (!($dbh = $this->getDI()->get('dbaudit'))) {
                                throw new DatabaseException("Failed get audit database connection");
                        }

                        $changes['changes'] = serialize($changes['changes']);

                        $tbl = $this->_options['table'];
                        $sql = "INSERT INTO `$tbl`(type, res, rid, user, time, changes) VALUES(?,?,?,?,?,?)";
                        $sth = $dbh->prepare($sql);
                        $res = $sth->execute(array_values($changes));

                        return $res;
                }
        }

}
