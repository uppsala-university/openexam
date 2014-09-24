<?php

namespace OpenExam\Library\Security;

use Phalcon\Acl;
use Phalcon\Acl\Adapter\Memory as AclAdapter;
use Phalcon\Acl\Role;
use Phalcon\Mvc\User\Component;

/**
 * Security
 *
 * This is the security plugin which controls that users only have access to the modules they're assigned to
 */
class Acl extends Component
{

        /**
         * Access list configuration.
         * @var array 
         */
        private $access;
        /**
         * The ACL object.
         * @var AclAdapter 
         */
        private $acl;

        /**
         * Constructor.
         * @param array $access Access list configuration.
         */
        public function __construct($access = array())
        {
                $this->access = $access;
        }

        /**
         * Get ACL object.
         * @return AclAdapter
         */
        public function getAcl()
        {
                if (!isset($this->acl)) {
                        if ($this->persistent->has('acl')) {
                                $this->acl = $this->persistent->get('acl');
                        } else {
                                $this->acl = $this->rebuild();
                                $this->persistent->set('acl', $this->acl);
                        }
                }
                return $this->acl;
        }

        /**
         * Returns true if role is permitted to call action on resource.
         * 
         * @param string $role
         * @param string $resource
         * @param string $action
         * @return bool
         */
        public function isAllowed($role, $resource, $action)
        {
                return $this->getAcl()->isAllowed($role, $resource, $action);
        }

        private function rebuild()
        {
                $acl = new AclAdapter();
                $acl->setDefaultAction(Acl::DENY);

                // 
                // Use roles map:
                // 
                $roles = $this->access['roles'];

                // 
                // Use permissions map:
                // 
                $permissions = $this->access['permissions'];

                // 
                // Add roles:
                // 
                foreach (array_keys($roles) as $role) {
                        $acl->addRole(new Role($role));
                }

                // 
                // Add resources:
                // 
                $resources = array();
                foreach ($roles as $role => $rules) {
                        if (is_array($rules)) {
                                foreach (array_keys($rules) as $resource) {
                                        $resources[] = $resource;
                                }
                        }
                }
                $resources = array_unique($resources);
                foreach ($resources as $resource) {
                        $acl->addResource($resource, $permissions['full']);
                }

                // 
                // Add rules:
                // 
                foreach ($roles as $role => $resources) {
                        if (is_string($resources)) {
                                $acl->allow($role, '*', $permissions[$resources]);
                                continue;
                        }
                        foreach ($resources as $resource => $permission) {
                                $acl->allow($role, $resource, $permissions[$permission]);
                        }
                }

                return $acl;
        }

}
