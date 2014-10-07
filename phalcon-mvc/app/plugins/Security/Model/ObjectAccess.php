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

use OpenExam\Library\Security\User;
use Phalcon\Events\Event;
use Phalcon\Mvc\Model;
use Phalcon\Mvc\User\Plugin;

/**
 * Abstract base class for object access control.
 * @author Anders Lövgren (QNET/BMC CompDept)
 */
abstract class ObjectAccess extends Plugin
{

        const CREATE = 'create';
        const READ = 'read';
        const UPDATE = 'update';
        const DELETE = 'delete';

        /**
         * Check model access.
         * @param string $action The model action.
         * @param Model $model The model.
         * @param User $user The peer object.
         */
        abstract function checkAccess($action, $model, $user);

        /**
         * Behaviour hook.
         * @param string $event The notify event name.
         * @param Model $model The model.
         * @param User $user The peer object.
         */
        public function notify($event, $model, $user)
        {
                switch ($event) {
                        case 'beforeCreate':
                                return $this->checkAccess(self::CREATE, $model, $user);
                        case 'afterFetch':
                                return $this->checkAccess(self::READ, $model, $user);
                        case 'beforeUpdate':
                                return $this->checkAccess(self::UPDATE, $model, $user);
                        case 'beforeDelete':
                                return $this->checkAccess(self::DELETE, $model, $user);
                }
        }

        /**
         * Delete event hook.
         * @param Event $event
         * @param Model $model
         * @param User $user The peer object.
         */
        protected function beforeDelete($event, $model, $user)
        {
                if ($this->logger->debug) {
                        $this->logger->debug->log(sprintf(
                                "%s(event=%s, model=%s, user=%s)", __METHOD__, $event->getType(), $model->getName(), $user->getPrincipalName()
                        ));
                }
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
                if ($this->logger->debug) {
                        $this->logger->debug->log(sprintf(
                                "%s(event=%s, model=%s, user=%s)", __METHOD__, $event->getType(), $model->getName(), $user->getPrincipalName()
                        ));
                }
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
                if ($this->logger->debug) {
                        $this->logger->debug->log(sprintf(
                                "%s(event=%s, model=%s, user=%s)", __METHOD__, $event->getType(), $model->getName(), $user->getPrincipalName()
                        ));
                }
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
                if ($this->logger->debug) {
                        $this->logger->debug->log(sprintf(
                                "%s(event=%s, model=%s, user=%s)", __METHOD__, $event->getType(), $model->getName(), $user->getPrincipalName()
                        ));
                }
                return $this->notify($event->getType(), $model, $user);
        }

}
