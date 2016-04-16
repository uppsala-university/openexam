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

use OpenExam\Library\Model\Behavior\ModelBehavior;
use Phalcon\Mvc\ModelInterface;

/**
 * Audit trail for models (base class).
 * 
 * This is the base class for implementing audit on a model. It should be
 * derived by specialized classes defining the actual storage by implementing
 * the write() method.
 * 
 * @see FileTargetAudit
 * @see DataTargetAudit
 * 
 * @author Anders Lövgren (QNET/BMC CompDept)
 */
abstract class Audit extends ModelBehavior
{

        /**
         * Map event type to action.
         * @var array 
         */
        private static $actionmap = array(
                'afterCreate' => 'create',
                'afterUpdate' => 'update',
                'afterDelete' => 'delete'
        );

        /**
         * Check if given event has changes to handle.
         * 
         * @param string $type The event type.
         * @param array $actions Array of supported actions.
         * @return boolean
         */
        protected function hasChanges($type, $actions)
        {
                if (array_key_exists($type, self::$actionmap) == false) {
                        return false;
                } elseif (in_array(self::$actionmap[$type], $actions) == false) {
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

                $data = $this->build($model, 'create');
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

                $data = $this->build($model, 'update');
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

                $data = $this->build($model, 'delete');
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
                        'model'   => $model->getResourceName(),
                        'id'      => $model->id,
                        'user'    => $di->get('user')->getPrincipalName(),
                        'time'    => date('Y-m-d H:i:s'),
                        'changes' => array()
                );
        }

        /**
         * Write changes to storage.
         * 
         * @param array $changes The changes.
         * @param array $options
         * @return int 
         */
        abstract protected function write($changes, $options, $di = null);
}
