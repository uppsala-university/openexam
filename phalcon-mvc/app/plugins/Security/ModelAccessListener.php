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
         * Check that ACL permit access to resource.
         * @param Event $event
         * @param Model $model
         * @param string $action
         * @return boolean
         * @throws Exception (acl|user|auth|access|role)
         */
        private function checkAccessList($event, $model, $action)
        {
                // 
                // Check system services:
                // 
                if (($acl = $this->getDI()->get('acl')) == false) {
                        throw new Exception('acl');
                }
                if (($user = $this->getDI()->get('user')) == false) {
                        throw new Exception('user');
                }

                // 
                // No primary role means unrestricted access. Make sure that
                // peer is authenticated if primary role is set.
                // 
                if ($user->hasPrimaryRole() == false) {
                        return true;    // unrestricted access
                } elseif ($user->getUser() == null) {
                        throw new Exception('auth');
                } else {
                        $role = $user->getPrimaryRole();
                }

                // 
                // Check that ACL permits access for this role:
                // 
                if ($acl->isAllowed($role, $model->getName(), $action) == false) {
                        throw new Exception('access');
                }

                // 
                // Verify that caller has requested role (global), if so,
                // trigger object specific role verification.
                // 
                if ($user->roles->aquire($role) == false) {
                        throw new Exception('role');
                } elseif (Roles::isCustom($role)) {
                        return true;    // Custom roles are global
                } else {
                        $this->_eventsManager->fire($model->getName() . ':' . $event->getType(), $model, $user);
                }
        }

        /**
         * Called before a model is created.
         * @param Event $event
         * @param Model $model
         */
        protected function beforeCreate($event, $model)
        {
                return $this->checkAccessList($event, $model, ObjectAccess::CREATE);
        }

        /**
         * Called before a model is updated.
         * @param Event $event
         * @param Model $model
         */
        protected function beforeUpdate($event, $model)
        {
                return $this->checkAccessList($event, $model, ObjectAccess::UPDATE);
        }

        /**
         * Called before a model is deleted.
         * @param Event $event
         * @param Model $model
         */
        protected function beforeDelete($event, $model)
        {
                return $this->checkAccessList($event, $model, ObjectAccess::DELETE);
        }

        /**
         * Called after a model is fetched (read).
         * @param Event $event
         * @param Model $model
         */
        protected function afterFetch($event, $model)
        {
                return $this->checkAccessList($event, $model, ObjectAccess::READ);
        }

}
