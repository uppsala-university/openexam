<?php

// 
// The source code is copyrighted, with equal shared rights, between the
// authors (see the file AUTHORS) and the OpenExam project, Uppsala University 
// unless otherwise explicit stated elsewhere.
// 
// File:    ObjectAccess.php
// Created: 2014-09-30 04:55:06
// 
// Author:  Anders Lövgren (QNET/BMC CompDept)
// 

namespace OpenExam\Plugins\Security\Model;

use Phalcon\Paginator\Adapter\Model;

/**
 * Abstract base class for object access control.
 * @author Anders Lövgren (QNET/BMC CompDept)
 */
abstract class ObjectAccess
{
        const CREATE = 'create';
        const READ = 'read';
        const UPDATE = 'update';
        const DELETE = 'delete';

        /**
         * Behavour hook.
         * @param string $event The notify event name.
         * @param Model $model The model.
         */
        abstract function notify($event, $model);

        /**
         * Delete event hook.
         * @param Event $event
         * @param Decoder $model
         */
        protected function beforeDelete($event, $model)
        {
                printf("%s: event=%s, model=%s\n", __METHOD__, $event->getType(), $model->getName());
                return $this->notify($event->getType(), $model);
        }

        /**
         * Create event hook.
         * @param Event $event
         * @param Decoder $model
         */
        protected function beforeCreate($event, $model)
        {
                printf("%s: event=%s, model=%s\n", __METHOD__, $event->getType(), $model->getName());
                return $this->notify($event->getType(), $model);
        }

        /**
         * Uodate event hook.
         * @param Event $event
         * @param Decoder $model
         */
        protected function beforeUpdate($event, $model)
        {
                printf("%s: event=%s, model=%s\n", __METHOD__, $event->getType(), $model->getName());
                return $this->notify($event->getType(), $model);
        }

        /**
         * Read event hook.
         * @param Event $event
         * @param Decoder $model
         */
        protected function afterFetch($event, $model)
        {
                printf("%s: event=%s, model=%s\n", __METHOD__, $event->getType(), $model->getName());
                return $this->notify($event->getType(), $model);
        }

}
