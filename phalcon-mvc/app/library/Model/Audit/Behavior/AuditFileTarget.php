<?php

// 
// The source code is copyrighted, with equal shared rights, between the
// authors (see the file AUTHORS) and the OpenExam project, Uppsala University 
// unless otherwise explicit stated elsewhere.
// 
// File:    AuditFileTarget.php
// Created: 2016-04-15 15:49:54
// 
// Author:  Anders Lövgren (Computing Department at BMC, Uppsala University)
// 

namespace OpenExam\Library\Model\Audit\Behavior;

use OpenExam\Library\Model\Audit\Audit;
use OpenExam\Library\Model\Audit\Config\AuditConfig;
use OpenExam\Library\Model\Audit\Target\FileTarget;
use Phalcon\Mvc\ModelInterface;

/**
 * Audit trail for models (file storage).
 * 
 * To implement audit on a model:
 * 
 * 1. Enable keep snapshots in the audited model.
 * 2. Call addBehavior(new AuditFileTarget()) in model initialize.
 *
 * <code>
 * protected function initialize()
 * {
 *      $this->keepSnapshots(true);
 *      $this->addBehavior(new AuditFileTarget());
 * }
 * </code>
 * 
 * The output format and log location can be customized by passing an options
 * array to the contructor:
 * 
 * <code>
 * $this->addBehavior(new AuditFileTarget(array(
 *      'format' => FileTarget::FORMAT_SERIALIZE,
 *      'file'   => '/tmp/output.log'
 * )));
 * </code>
 * 
 * @author Anders Lövgren (Computing Department at BMC, Uppsala University)
 */
class AuditFileTarget extends Audit
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
                                $target = new FileTarget($audit->getConfig(), $model);
                                return $target->write($changes);
                        }
                }
        }

}
