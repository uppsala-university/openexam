<?php

// 
// The source code is copyrighted, with equal shared rights, between the
// authors (see the file AUTHORS) and the OpenExam project, Uppsala University 
// unless otherwise explicit stated elsewhere.
// 
// File:    DataTargetAudit.php
// Created: 2016-04-15 15:59:30
// 
// Author:  Anders Lövgren (Computing Department at BMC, Uppsala University)
// 

namespace OpenExam\Library\Model\Audit;

use Phalcon\DiInterface;
use Phalcon\Mvc\ModelInterface;
use OpenExam\Library\Database\Exception as DatabaseException;

/**
 * Audit trail for models (database storage).
 * 
 * To implement audit on a model:
 * 
 * 1. Enable keep snapshots in the audited model.
 * 2. Call addBehavior(new DataTargetAudit()) in model initialize.
 *
 * <code>
 * protected function initialize()
 * {
 *      $this->keepSnapshots(true);
 *      $this->addBehavior(new DataTargetAudit());
 * }
 * </code>
 * 
 * The database connection name and table can be customized by passing an 
 * options array to the contructor:
 * 
 * <code>
 * $this->addBehavior(new DataTargetAudit(array(
 *      'connection' => 'dbaudit',
 *      'table'      => 'audit'
 * )));
 * </code>
 * 
 * @author Anders Lövgren (Computing Department at BMC, Uppsala University)
 */
class DataTargetAudit extends Audit
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
         * Receives notifications from the Models Manager
         *
         * @param string $type The event type.
         * @param ModelInterface $model The target model.
         */
        public function notify($type, $model)
        {
                $options = $this->getOptions();

                if (!isset($options)) {
                        $options = array();
                }
                if (!isset($options['actions'])) {
                        $options['actions'] = array('create', 'update', 'delete');
                }
                if (!isset($options['connection'])) {
                        $options['connection'] = self::CONNECTION_NAME;
                }
                if (!isset($options['table'])) {
                        $options['table'] = self::TABLE_NAME;
                }

                $actions = $options['actions'];

                if (!parent::hasChanges($type, $actions)) {
                        return true;
                }
                if (($changes = parent::getChanges($type, $model))) {
                        return $this->write($changes, $options, $model->getDI());
                }
        }

        /**
         * Write changes to database.
         * 
         * @param array $changes The model changes.
         * @param array $options The behavior options.
         * @param DiInterface The dependency injector.
         * @return int 
         */
        protected function write($changes, $options, $di = null)
        {
                $changes['changes'] = serialize($changes['changes']);

                if ($di->has('dbaudit')) {
                        if (!($dbh = $di->get('dbaudit'))) {
                                throw new DatabaseException("Failed get audit database connection");
                        }
                        
                        $tbl = $options['table'];
                        $sql = "INSERT INTO $tbl(type, model, id, user, time, changes) VALUES(?,?,?,?,?,?)";
                        $sth = $dbh->prepare($sql);
                        $res = $sth->execute(array_values($changes));
                        
                        return $res;
                }
        }

}
