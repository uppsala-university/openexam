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

/**
 * Extends behavour of model class.
 *
 * @author Anders Lövgren (QNET/BMC CompDept)
 */
trait AuthorizationTrait
{

        private $rrole;

        protected function afterFetch()
        {
                printf("%s: %s\n", __METHOD__, print_r($this->dump(), true));
                $this->authorize(self::read);
        }

        protected function beforeCreate()
        {
                printf("%s: %s\n", __METHOD__, print_r($this->dump(), true));
                $this->authorize(self::create);
        }

        protected function beforeUpdate()
        {
                printf("%s: %s\n", __METHOD__, print_r($this->dump(), true));
                $this->authorize(self::update);
        }

        protected function beforeDelete()
        {
                printf("%s: %s\n", __METHOD__, print_r($this->dump(), true));
                $this->authorize(self::delete);
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
                $this->rrole = $role;
        }

        /**
         * Return true if role is set.
         * @return bool
         */
        public function hasRole()
        {
                return isset($this->rrole);
        }

        /**
         * Get role for checked access.
         * @return string
         */
        public function getRole()
        {
                return $this->rrole;
        }

        private static function getUser()
        {
                if (($session = self::getService('session')) == false) {
                        throw new Exception(_("Session service ('session') is missing."));
                }
                if (($auth = $session->get('auth')) == false) {
                        throw new Exception(_("User session data ('auth') is missing."));
                }
                if (!isset($auth['user'])) {
                        throw new Exception(_("The calling user is missing in session data."));
                }

                return $auth['user'];
        }

        private function authorize($action)
        {
                if (isset($this->rrole)) {
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

        private static function getService($name)
        {
                return \Phalcon\DI::getDefault()->get($name);
        }

        private function checkAccess($action)
        {
                if (($acl = self::getService('acl')) == false) {
                        throw new Exception(_("ACL service ('acl') is missing."));
                }
                if ($acl->isAllowed($this->rrole, $this->getName(), $action) == false) {
                        throw new Exception(_("You are not authorized to make this call."));
                }
        }

}
