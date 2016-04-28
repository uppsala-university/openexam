<?php

// 
// The source code is copyrighted, with equal shared rights, between the
// authors (see the file AUTHORS) and the OpenExam project, Uppsala University 
// unless otherwise explicit stated elsewhere.
// 
// File:    AuditDataTarget.php
// Created: 2016-04-15 15:59:30
// 
// Author:  Anders Lövgren (Computing Department at BMC, Uppsala University)
// 

namespace OpenExam\Library\Model\Audit\Behavior;

use OpenExam\Library\Model\Audit\Audit;
use OpenExam\Library\Model\Audit\Config\AuditConfig;
use OpenExam\Library\Model\Audit\Target\DataTarget;
use Phalcon\Mvc\ModelInterface;

/**
 * Audit trail for models (database storage).
 * 
 * Implement audit on a model by enable snapshots and adding the database
 * target audit.
 *
 * <code>
 * protected function initialize()
 * {
 *      $this->keepSnapshots(true);
 *      $this->addBehavior(new AuditDataTarget(array(
 *              'afterCreate' => array(
 *                      'table'      => 'audit',
 *                      'connection' => 'dbaudit'
 *              ),
 *              'afterUpdate' => array(
 *                      'table'      => 'audit',
 *                      'connection' => 'dbaudit'
 *              ),
 *              'afterDelete' => array(
 *                      'table'      => 'audit',
 *                      'connection' => 'dbaudit'
 *              )
 *      )));
 * }
 * </code>
 * 
 * @see Audit
 * @author Anders Lövgren (Computing Department at BMC, Uppsala University)
 */
class AuditDataTarget extends Audit
{

        /**
         * Receives notifications from the Models Manager
         *
         * @param string $type The event type.
         * @param ModelInterface $model The target model.
         */
        public function notify($type, $model)
        {
                if (($options = $this->getOptions($type))) {
                        $audit = new AuditConfig(array(
                                Audit::TARGET_DATA => $options
                        ));

                        if (($changes = parent::getChanges($type, $model))) {
                                $config = $audit->getTarget(Audit::TARGET_DATA);
                                $target = new DataTarget($config, $model);
                                return $target->write($changes);
                        }
                }
        }

}
