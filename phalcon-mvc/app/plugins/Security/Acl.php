<?php

namespace OpenExam\Plugins\Security;

use Phalcon\Events\Event,
    Phalcon\Mvc\User\Plugin,
    Phalcon\Mvc\Dispatcher,
    Phalcon\Acl\Role,
    Phalcon\Acl\Adapter\Memory as AclAdapter;

/**
 * Security
 *
 * This is the security plugin which controls that users only have access to the modules they're assigned to
 */
class Acl extends Plugin
{

        private $access;

        public function __construct($access = array())
        {
                $this->access = $access;
        }

        public function getAcl()
        {
                if (!isset($this->persistent->acl)) {
                        $this->persistent->acl = $this->rebuild();
                }
                return $this->persistent->acl;
        }

        /**
         * This action is executed before execute any action in the application
         */
        public function beforeDispatch(Event $event, Dispatcher $dispatcher)
        {
                $auth = $this->session->get('auth');
                if (!$auth) {
                        $role = 'Guests';
                } else {
                        $role = 'Admin';
                }

                $controller = $dispatcher->getControllerName();
                $action = $dispatcher->getActionName();

                $acl = $this->getAcl();

                $allowed = $acl->isAllowed($role, $controller, $action);
                if ($allowed != Acl::ALLOW) {
                        $this->flash->error("You don't have access to this module");
                        $dispatcher->forward(
                            array(
                                    'controller' => 'index',
                                    'action'     => 'index'
                            )
                        );
                        return false;
                }
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
                $acl->setDefaultAction(\Phalcon\Acl::DENY);

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
