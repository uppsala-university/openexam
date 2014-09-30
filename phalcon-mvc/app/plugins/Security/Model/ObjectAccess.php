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

use Phalcon\Events\Event;
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
         * @param User $user The peer object.
         */
        abstract function notify($event, $model, $user);

        /**
         * Delete event hook.
         * @param Event $event
         * @param Model $model
         * @param User $user The peer object.
         */
        protected function beforeDelete($event, $model, $user)
        {
                printf("%s: event=%s, model=%s\n", __METHOD__, $event->getType(), $model->getName());
                return $this->notify($event->getType(), $model, $user);
        }

        /**
         * Create event hook.
         * @param Event $event
         * @param Model $model
         * @param User $user The peer object.
         */
        protected function beforeCreate($event, $model, $user)
        {
                printf("%s: event=%s, model=%s\n", __METHOD__, $event->getType(), $model->getName());
                return $this->notify($event->getType(), $model, $user);
        }

        /**
         * Uodate event hook.
         * @param Event $event
         * @param Model $model
         * @param User $user The peer object.
         */
        protected function beforeUpdate($event, $model, $user)
        {
                printf("%s: event=%s, model=%s\n", __METHOD__, $event->getType(), $model->getName());
                return $this->notify($event->getType(), $model, $user);
        }

        /**
         * Read event hook.
         * @param Event $event
         * @param Model $model
         * @param User $user The peer object.
         */
        protected function afterFetch($event, $model, $user)
        {
                printf("%s: event=%s, model=%s\n", __METHOD__, $event->getType(), $model->getName());
                return $this->notify($event->getType(), $model, $user);
        }

}
