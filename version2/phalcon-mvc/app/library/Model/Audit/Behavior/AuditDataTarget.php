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
 * To implement audit on a model:
 * 
 * 1. Enable keep snapshots in the audited model.
 * 2. Call addBehavior(new AuditDataTarget()) in model initialize.
 *
 * <code>
 * protected function initialize()
 * {
 *      $this->keepSnapshots(true);
 *      $this->addBehavior(new AuditDataTarget());
 * }
 * </code>
 * 
 * The database connection name and table can be customized by passing an 
 * options array to the contructor:
 * 
 * <code>
 * $this->addBehavior(new AuditDataTarget(array(
 *      'connection' => 'dbaudit',
 *      'table'      => 'audit'
 * )));
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
                $audit = new AuditConfig($this->getOptions());

                if (parent::hasChanges($type, $audit->getActions())) {
                        if (($changes = parent::getChanges($type, $model))) {
                                $target = new DataTarget($audit->getConfig(), $model);
                                return $target->write($changes);
                        }
                }
        }

}
