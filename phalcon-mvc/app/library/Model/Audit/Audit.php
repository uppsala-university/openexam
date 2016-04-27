<?php

// 
// The source code is copyrighted, with equal shared rights, between the
// authors (see the file AUTHORS) and the OpenExam project, Uppsala University 
// unless otherwise explicit stated elsewhere.
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
 * Audit behavior.
 * 
 * Notice that this audit behavior should be initialized with an audit
 * config object, not an array:
 * 
 * <code>
 * $config = new AuditConfig(...);
 * $object = new Audit($config);
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
        public function notify($type, $model)
        {
                $options = $this->getOptions();

                if ($this->hasChanges($type, $options->getActions())) {
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
        protected function hasChanges($type, $actions)
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
                return $data;
        }

        /**
         * Called in response to model update event.
         * @param ModelInterface $model The target model.
         */
        private function afterUpdate($model)
        {
                if (!$model->hasSnapshotData()) {
                        return false;
                }

                $changed = $model->getChangedFields();

                $data = $this->build($model, ObjectAccess::UPDATE);
                $prev = $model->getSnapshotData();

                if (count($changed) > 0) {
                        foreach ($changed as $field) {
                                $data['changes'][$field] = array(
                                        'old' => $prev[$field],
                                        'new' => $model->$field
                                );
                        }
                }
                return $data;
        }

        /**
         * Called in response to model delete event.
         * @param ModelInterface $model The target model.
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
                return $data;
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
