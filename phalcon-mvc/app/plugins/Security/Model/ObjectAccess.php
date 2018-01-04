<?php

/*
 * Copyright (C) 2014-2018 The OpenExam Project
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
// File:    ObjectAccess.php
// Created: 2014-09-30 04:55:06
// 
// Author:  Anders Lövgren (QNET/BMC CompDept)
// 

namespace OpenExam\Plugins\Security\Model;

use Exception;
use OpenExam\Library\Security\Roles;
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
         * @param Model $model The model object.
         * @param User $user The peer object.
         * @return boolean
         */
        public final function checkAccess($action, $model, $user)
        {
                if ($this->logger->debug) {
                        $this->logger->debug->log(sprintf(
                                "%s(action=%s, model=%s, user=%s)", __METHOD__, $action, $model->getResourceName(), $user->getPrincipalName()
                        ));
                }

                if ($this->checkObjectRole($action, $model, $user)) {
                        if ($this->checkObjectAction($action, $model, $user)) {
                                return true;
                        }
                }

                return false;
        }

        /**
         * Adapter function for model role verification.
         * 
         * Sub classes can override this function to provide role based
         * access control on this specific model object. That is, verify that
         * the user has the requested role on this model object.
         * 
         * @param string $action The model action.
         * @param Model $model The model object.
         * @param User $user The peer object.
         * @return boolean
         */
        public function checkObjectRole($action, $model, $user)
        {
                if ($this->logger->debug) {
                        $this->logger->debug->log(sprintf(
                                "%s(action=%s, model=%s, user=%s)", __METHOD__, $action, $model->getResourceName(), $user->getPrincipalName()
                        ));
                }

                return true;
        }

        /**
         * Adapter function for model action verification.
         * 
         * Sub classes can override this function to provide action based
         * access control on this specific model object. That is, verify that
         * the user has permissions to perform requested action on this model
         * object (business rule).
         * 
         * @param string $action The model action.
         * @param Model $model The model object.
         * @param User $user The peer object.
         * @return boolean
         */
        public function checkObjectAction($action, $model, $user)
        {
                if ($this->logger->debug) {
                        $this->logger->debug->log(sprintf(
                                "%s(action=%s, model=%s, user=%s)", __METHOD__, $action, $model->getResourceName(), $user->getPrincipalName()
                        ));
                }

                return true;
        }

        /**
         * Behaviour hook.
         * @param string $event The notify event name.
         * @param Model $model The model object.
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
                                "%s(event=%s, model=%s, user=%s)", __METHOD__, $event->getType(), $model->getResourceName(), $user->getPrincipalName()
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
                                "%s(event=%s, model=%s, user=%s)", __METHOD__, $event->getType(), $model->getResourceName(), $user->getPrincipalName()
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
                                "%s(event=%s, model=%s, user=%s)", __METHOD__, $event->getType(), $model->getResourceName(), $user->getPrincipalName()
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
                                "%s(event=%s, model=%s, user=%s)", __METHOD__, $event->getType(), $model->getResourceName(), $user->getPrincipalName()
                        ));
                }
                return $this->notify($event->getType(), $model, $user);
        }

        /**
         * Invoke callback function in trusted context.
         * 
         * This methods allowes access control callback (e.g. closures) to be 
         * executed in a trusted context by temporary disable the access
         * control during execution of the callback function.
         * 
         * The callback can return values or throw exceptions. This method
         * will restore the original context before returning result from
         * callback function or re-throwing an catched exception.
         * 
         * The callback function will be invoked with the original role as 
         * its sole argument:
         * 
         * <code>
         * return parent::trustedCall(
         *      function($role) use($action, $model, $user) { 
         *              // perform access control... 
         *      }
         * );
         * </code>
         * 
         * @param callable $callback The callback function.
         * @return bool 
         * @throws Exception
         */
        protected function trustedContextCall($callback)
        {
                $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2)[1];
                $this->logger->access->debug(print_r($trace, true));

                // 
                // Skip roles check for trusted calls:
                // 
                if (($role = $this->user->getPrimaryRole()) == Roles::TRUSTED) {
                        if ($trace['function'] == 'checkObjectRole') {
                                return true;
                        }
                }

                // 
                // Invoke callback function in trusted context:
                // 
                try {
                        $this->user->setPrimaryRole(Roles::TRUSTED);
                        $result = $callback($role);
                        $this->user->setPrimaryRole($role);
                        return $result;
                } catch (Exception $exception) {
                        $this->user->setPrimaryRole($role);
                        throw $exception;
                }
        }

}
