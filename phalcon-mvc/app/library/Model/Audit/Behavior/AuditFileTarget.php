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
 * Implement audit on a model by enable snapshots and adding the database
 * target audit.
 *
 * <code>
 * protected function initialize()
 * {
 *      $this->keepSnapshots(true);
 *      $this->addBehavior(new AuditFileTarget(array(
 *              'afterCreate' => array(
 *                      'format' => FileTarget::FORMAT_SERIALIZE,
 *                      'file'   => '/tmp/audit-model.log'
 *              ),
 *              'afterUpdate' => array(
 *                      'format' => FileTarget::FORMAT_SERIALIZE,
 *                      'file'   => '/tmp/audit-model.log'
 *              ),
 *              'afterDelete' => array(
 *                      'format' => FileTarget::FORMAT_SERIALIZE,
 *                      'file'   => '/tmp/audit-model.log'
 *              )
 *      )));
 * }
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
                if (($options = $this->getOptions($type))) {
                        $audit = new AuditConfig(array(
                                Audit::TARGET_FILE => $options
                        ));

                        if (($changes = parent::getChanges($type, $model))) {
                                $config = $audit->getTarget(Audit::TARGET_FILE);
                                $target = new FileTarget($config, $model);
                                return $target->write($changes);
                        }
                }
        }

}
