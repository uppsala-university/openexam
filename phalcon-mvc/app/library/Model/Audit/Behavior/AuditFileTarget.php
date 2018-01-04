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
