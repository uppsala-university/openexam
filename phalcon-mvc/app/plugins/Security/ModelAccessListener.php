<?php

// 
// The source code is copyrighted, with equal shared rights, between the
// authors (see the file AUTHORS) and the OpenExam project, Uppsala University 
// unless otherwise explicit stated elsewhere.
// 
// File:    ModelAccessListener.php
// Created: 2014-09-26 10:32:13
// 
// Author:  Anders Lövgren (Computing Department at BMC, Uppsala University)
// 

namespace OpenExam\Plugins\Security;

use OpenExam\Library\Security\Exception;
use OpenExam\Library\Security\Roles;
use OpenExam\Plugins\Security\Model\ObjectAccess;
use Phalcon\Events\Event;
use Phalcon\Events\EventsAwareInterface;
use Phalcon\Mvc\Model;
use Phalcon\Mvc\User\Plugin;

/**
 * Centralized model access handler.
 * 
 * This class ensures that:
 * 
 * 1. The system services are present (acl and user).
 * 2. User is authenticated (if primary role is set).
 * 3. The requested primary role has been global aquired.
 * 4. The ACL allows access to the requested resource/action for role.
 * 
 * Object specific access control is provided by classes derived from 
 * Model\ObjectAccess.
 *
 * @author Anders Lövgren (Computing Department at BMC, Uppsala University)
 */
class ModelAccessListener extends Plugin implements EventsAwareInterface
{

        /**
         * @var callable 
         */
        private $callable;
        /**
         * @var array 
         */
        private $register;

        /**
         * Constructor.
         * @param callable $callable
         * @param array $register
         */
        public function __construct($callable, $register = array())
        {
                $this->callable = $callable;
                $this->register = $register;
        }

        /**
         * Check that caller has access to resource.
         * @param Event $event The event to check.
         * @param Model $model The model to check.
         * @param string $action The action to perform.
         * @return boolean
         * @throws Exception message=(acl|user|auth|access|role)
         */
        public function checkAccess($event, $model, $action)
        {
                $type = $event->getType();
                $name = $model->getResourceName();
                $addr = $this->request->getClientAddress();

                if ($this->logger->debug) {
                        $this->logger->debug->log(sprintf(
                                "%s(event=%s, model=%s, action=%s)", __METHOD__, $type, $name, $action
                        ));
                }

                // 
                // Check system services:
                // 
                if (($acl = $this->getDI()->get('acl')) == false) {
                        $this->logger->system->critical("The ACL service ('acl') is missing.");
                        throw new Exception('acl');
                }
                if (($user = $this->getDI()->get('user')) == false) {
                        $this->logger->system->critical("The User service ('user') is missing.");
                        throw new Exception('user');
                } else {
                        $principal = $user->getPrincipalName();
                }

                // 
                // No primary role means unrestricted access. Make sure that
                // peer is authenticated if primary role is set.
                // 
                if ($user->hasPrimaryRole() == false) {
                        $this->logger->auth->info(sprintf(
                                "Granted %s access on %s(id=%d) to user %s (primary role unset) [%s]", $action, $name, $model->id, $principal, $addr
                        ));
                        return true;    // unrestricted access
                } elseif (($role = $user->getPrimaryRole()) === Roles::TRUSTED) {
                        $this->logger->auth->info(sprintf(
                                "Granted %s access on %s(id=%d) for %s (trusted role) [%s]", $action, $name, $model->id, $role, $addr
                        ));
                        return true;    // Control ACL System
                } elseif (($role = $user->getPrimaryRole()) === Roles::SYSTEM) {
                        $this->logger->auth->info(sprintf(
                                "Granted %s access on %s(id=%d) for %s (trusted role) [%s]", $action, $name, $model->id, $role, $addr
                        ));
                        return true;    // System internal
                } elseif ($user->getUser() == null) {
                        $this->logger->auth->error(sprintf(
                                "Denied %s access on %s(id=%d) (unauthenticated user) [%s]", $action, $name, $model->id, $addr
                        ));
                        throw new Exception('auth');
                }

                // 
                // Check that ACL permits access for this role:
                // 
                if ($acl->isAllowed($role, $name, $action) == false) {
                        $this->logger->auth->error(sprintf(
                                "Denied %s access on %s(id=%d) for user %s using role %s (blocked by ACL) [%s]", $action, $name, $model->id, $principal, $role, $addr
                        ));
                        throw new Exception('access');
                }

                // 
                // Verify that caller has requested role (global), if so,
                // trigger object specific role verification.
                // 
                if ($user->roles->aquire($role) == false) {
                        $this->logger->auth->error(sprintf(
                                "Denied %s access on %s(id=%d) for user %s using role %s (failed aquire role) [%s]", $action, $name, $model->id, $principal, $role, $addr
                        ));
                        throw new Exception('role');
                } elseif ($action == ObjectAccess::CREATE) {
                        $this->logger->auth->info(sprintf(
                                "Granted %s access on %s for user %s using role %s [%s]", $action, $name, $model->id, $principal, $role, $addr
                        ));
                        return true;    // The create action is not connected with an object.
                } elseif (Roles::isCustom($role)) {
                        $this->logger->auth->info(sprintf(
                                "Granted %s access on %s(id=%d) for user %s using role %s [%s]", $action, $name, $model->id, $principal, $role, $addr
                        ));
                        return true;    // Custom roles are global
                } elseif (($access = $this->getObjectAccess($name))) {
                        $this->logger->debug->log(sprintf(
                                "%s(event=%s, model=%s, user=%s) [calling]", get_class($access), $type, $name, $principal
                        ));
                        if ($access->notify($type, $model, $user)) {
                                $this->logger->auth->info(sprintf(
                                        "Granted %s access on %s(id=%d) for user %s using role %s [%s]", $action, $name, $model->id, $principal, $role, $addr
                                ));
                        }
                } else {
                        return false;
                }
        }

        /**
         * Called before a model is created.
         * @param Event $event
         * @param Model $model
         */
        protected function beforeCreate($event, $model)
        {
                return $this->checkAccess($event, $model, ObjectAccess::CREATE);
        }

        /**
         * Called before a model is updated.
         * @param Event $event
         * @param Model $model
         */
        protected function beforeUpdate($event, $model)
        {
                return $this->checkAccess($event, $model, ObjectAccess::UPDATE);
        }

        /**
         * Called before a model is deleted.
         * @param Event $event
         * @param Model $model
         */
        protected function beforeDelete($event, $model)
        {
                return $this->checkAccess($event, $model, ObjectAccess::DELETE);
        }

        /**
         * Called after a model is fetched (read).
         * @param Event $event
         * @param Model $model
         */
        protected function afterFetch($event, $model)
        {
                return $this->checkAccess($event, $model, ObjectAccess::READ);
        }

        /**
         * Get object access check object.
         * @param string $name The model name.
         * @return ObjectAccess
         */
        private function getObjectAccess($name)
        {
                if (!isset($this->register[$name])) {
                        $loader = $this->callable;
                        $this->register[$name] = $loader($name);
                }
                return $this->register[$name];
        }

}
