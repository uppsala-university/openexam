<?php

// 
// The source code is copyrighted, with equal shared rights, between the
// authors (see the file AUTHORS) and the OpenExam project, Uppsala University 
// unless otherwise explicit stated elsewhere.
// 
// File:    ModelTrait.php
// Created: 2014-08-27 22:14:01
// 
// Author:  Anders Lövgren (QNET/BMC CompDept)
// 

namespace OpenExam\Models\Access\Traits;

use OpenExam\Library\Security\Roles;

/**
 * Model authorization trait.
 * 
 * This trait adds behavour to the model class using this trait. It adds
 * authorization on create, read, update and delete. Authori
 * 
 * @author Anders Lövgren (QNET/BMC CompDept)
 */
trait AuthorizationTrait
{

        /**
         * The requested role.
         * @var string 
         */
        private $_role;
        /**
         * The roles aquisition object.
         * @var Roles 
         */
        private $_roles;

        protected function afterFetch()
        {
                printf("%s: %s\n", __METHOD__, print_r($this->dump(), true));
                $this->authorize(self::READ);
        }

        protected function beforeCreate()
        {
                printf("%s: %s\n", __METHOD__, print_r($this->dump(), true));
                $this->authorize(self::CREATE);
        }

        protected function beforeUpdate()
        {
                printf("%s: %s\n", __METHOD__, print_r($this->dump(), true));
                $this->authorize(self::UPDATE);
        }

        protected function beforeDelete()
        {
                printf("%s: %s\n", __METHOD__, print_r($this->dump(), true));
                $this->authorize(self::DELETE);
        }

        /**
         * Set role for checked access.
         * 
         * If role is unset, then model access is unchecked. If set, then
         * authorization against this role is enforced for create, read 
         * (fetch), update and delete operations on this model. 
         * 
         * @param string $role The access role.
         */
        public function setRole($role)
        {
                $this->_role = $role;
        }

        /**
         * Return true if role is set.
         * @return bool
         */
        public function hasRole()
        {
                return isset($this->_role);
        }

        /**
         * Get role for checked access.
         * @return string
         */
        public function getRole()
        {
                return $this->_role;
        }

        private function authorize($action)
        {
                if (isset($this->_role)) {
                        if (($user = $this->getDI()->get('user')) == false) {
                                throw new Exception(_("The user service ('user') is missing."));
                        } else {
                                $this->_roles = $user->roles;
                        }

                        if (method_exists($this, 'checkAccess')) {
                                $this->checkAccess($action);
                        }
                        if (method_exists($this, 'checkRole')) {
                                $this->checkRole();
                        }
                        if (method_exists($this, 'checkObject')) {
                                $this->checkObject();
                        }
                }
        }

        private function checkAccess($action)
        {
                if (($acl = $this->getDI()->get('acl')) == false) {
                        throw new Exception(_("The ACL service ('acl') is missing."));
                }
                if ($acl->isAllowed($this->_role, $this->getName(), $action) == false) {
                        throw new Exception(_("You are not authorized to make this call."));
                }
        }

}
