<?php

/*
 * Copyright (C) 2015-2018 The OpenExam Project
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
// File:    Audit.php
// Created: 2015-02-17 23:25:15
// 
// Author:  Anders Lövgren (QNET/BMC CompDept)
// 

namespace OpenExam\Library\Model\Audit;

use OpenExam\Library\Model\Audit\Behavior\AuditDataTarget;
use OpenExam\Library\Model\Audit\Behavior\AuditFileTarget;
use OpenExam\Library\Model\Audit\Config\AuditConfig;
use OpenExam\Library\Model\Audit\Target\AuditTarget;
use OpenExam\Library\Model\Audit\Target\DataTarget;
use OpenExam\Library\Model\Audit\Target\FileTarget;
use OpenExam\Library\Model\Behavior\ModelBehavior;
use OpenExam\Plugins\Security\Model\ObjectAccess;
use Phalcon\Mvc\ModelInterface;

/**
 * Multi target audit behavior.
 * 
 * This audit supports writing model snapshot changes to multiple targets 
 * at once. It's intended to be used together with the audit service that
 * gets configured in app/config/config.def
 * 
 * When used together with the audit service, the initialization is equivalent
 * to this example. Notice that the audit object is initialized with an
 * config object, not an array:
 * 
 * <code>
 * protected function initialize()
 * {
 *      $config = new AuditConfig(array(
 *              'data' => array(
 *                      'table'      => 'audit',
 *                      'connection' => 'dbaudit'
 *              ),
 *              'file' => array(
 *                      'format'     => 'serialize',
 *                      'file'       => '/tmp/audit-model.dat'
 *              ),
 *              'actions' => array( 
 *                      'create', 'update', 'delete' 
 *              )
 *      ));
 *      $this->keepSnapshots(true);
 *      $this->addBehavior(new Audit($config)); // Use defined actions.
 * }
 * </code>
 * 
 * Standard declaration explicit listing supported events are also supported:
 * 
 * <code>
 * protected function initialize()
 * {
 *      $config = array(
 *              'data' => array(
 *                      'table'      => 'audit',
 *                      'connection' => 'dbaudit'
 *              ),
 *              'file' => array(
 *                      'format'     => 'serialize',
 *                      'file'       => '/tmp/audit-model.dat'
 *              )
 *      ));
 *      $this->keepSnapshots(true);
 *      $this->addBehavior(new Audit(array(
 *              'afterCreate' => $config,
 *              'afterUpdate' => $config,
 *              'afterDelete' => $config
 *      )));
 * }
 * </code>
 * 
 * @see AuditFileTarget
 * @see AuditDataTarget
 * 
 * @author Anders Lövgren (QNET/BMC CompDept)
 */
class Audit extends ModelBehavior
{

        /**
         * Constant for file target audit.
         */
        const TARGET_FILE = 'file';
        /**
         * Constant for database target audit.
         */
        const TARGET_DATA = 'data';

        /**
         * Map event type to action.
         * @var array 
         */
        private static $_actionmap = array(
                'afterCreate' => ObjectAccess::CREATE,
                'afterUpdate' => ObjectAccess::UPDATE,
                'afterDelete' => ObjectAccess::DELETE
        );
        /**
         * Cached audit targets.
         * @var AuditTarget[]
         */
        private $_targets;

        /**
         * Receives notifications from the Models Manager
         *
         * @param string $type The event type.
         * @param ModelInterface $model The target model.
         */
        public function notify($type, ModelInterface $model)
        {
                if (($options = $this->getOptions($type))) {
                        $options = new AuditConfig($options);
                } else {
                        $options = $this->getOptions();
                }

                if ($options instanceof AuditConfig) {
                        if ($this->canHandle($type, $options->getActions())) {
                                if (!($changes = $this->getChanges($type, $model))) {
                                        return false;
                                }
                                if (!($targets = $this->getTargets($options, $model))) {
                                        return false;
                                }

                                foreach ($targets as $target) {
                                        $target->write($changes);
                                }
                                return true;
                        }
                }
        }

        /**
         * Get supported actions.
         * @return array
         */
        public static function getDefaultActions()
        {
                return array_values(self::$_actionmap);
        }

        /**
         * Get configured targets.
         * @param AuditConfig $options The target options.
         * @param ModelInterface $model The target model.
         * @return AuditTarget[]
         */
        private function getTargets($options, $model)
        {
                if (isset($this->_targets)) {
                        return $this->_targets;
                } else {
                        $this->_targets = array();
                }

                if ($options->hasTarget(self::TARGET_DATA)) {
                        $this->_targets[] = new DataTarget(
                            $options->getTarget(self::TARGET_DATA), $model
                        );
                }
                if ($options->hasTarget(self::TARGET_FILE)) {
                        $this->_targets[] = new FileTarget(
                            $options->getTarget(self::TARGET_FILE), $model
                        );
                }

                if (count($this->_targets) == 0) {
                        $this->_targets = false;
                }

                return $this->_targets;
        }

        /**
         * Check if given event has changes to handle.
         * 
         * @param string $type The event type.
         * @param array $actions Array of supported actions.
         * @return boolean
         */
        protected function canHandle($type, $actions)
        {
                if (array_key_exists($type, self::$_actionmap) == false) {
                        return false;
                } elseif (in_array(self::$_actionmap[$type], $actions) == false) {
                        return false;
                } else {
                        return true;
                }
        }

        /**
         * Get model changes.
         * 
         * @param string $type The event type.
         * @param ModelInterface $model The target model.
         * @return array|boolean Return changes or false if not changed.
         */
        protected function getChanges($type, $model)
        {
                if ($type == 'afterCreate') {
                        return $this->afterCreate($model);
                }
                if ($type == 'afterUpdate') {
                        return $this->afterUpdate($model);
                }
                if ($type == 'afterDelete') {
                        return $this->afterDelete($model);
                }
        }

        /**
         * Called in response to model create event.
         * @param ModelInterface $model The target model.
         * @return array|boolean Return changes or false if not changed.
         */
        private function afterCreate($model)
        {
                $changed = $model->toArray();

                $data = $this->build($model, ObjectAccess::CREATE);
                foreach ($changed as $field => $value) {
                        $data['changes'][$field] = array(
                                'old' => null,
                                'new' => $value
                        );
                }

                if (count($data['changes']) == 0) {
                        return false;
                } else {
                        return $data;
                }
        }

        /**
         * Called in response to model update event.
         * @param ModelInterface $model The target model.
         * @return array|boolean Return changes or false if not changed.
         */
        private function afterUpdate($model)
        {
                if (!$model->hasSnapshotData()) {
                        return false;
                }

                $changed = $model->getUpdatedFields();

                $data = $this->build($model, ObjectAccess::UPDATE);
                $prev = $model->getOldSnapshotData();

                if (count($changed) > 0) {
                        foreach ($changed as $field) {
                                $data['changes'][$field] = array(
                                        'old' => $prev[$field],
                                        'new' => $model->$field
                                );
                        }
                }

                if (count($data['changes']) == 0) {
                        return false;
                } else {
                        return $data;
                }
        }

        /**
         * Called in response to model delete event.
         * @param ModelInterface $model The target model.
         * @return array|boolean Return changes or false if not changed.
         */
        private function afterDelete($model)
        {
                $changed = $model->toArray();

                $data = $this->build($model, ObjectAccess::DELETE);
                foreach ($changed as $field => $value) {
                        $data['changes'][$field] = array(
                                'old' => $value,
                                'new' => null
                        );
                }

                if (count($data['changes']) == 0) {
                        return false;
                } else {
                        return $data;
                }
        }

        /**
         * Build change log (audit) array.
         * 
         * @param ModelInterface $model The target model.
         * @param string $type The action type (e.g. update).
         * @return array
         */
        private function build($model, $type)
        {
                $di = $model->getDI();

                return array(
                        'type'    => $type,
                        'res'     => $model->getResourceName(),
                        'rid'     => $model->id,
                        'user'    => $di->get('user')->getPrincipalName(),
                        'time'    => date('Y-m-d H:i:s'),
                        'changes' => array()
                );
        }

}
