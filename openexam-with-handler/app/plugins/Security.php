<?php

use Phalcon\Events\Event,
    Phalcon\Mvc\User\Plugin,
    Phalcon\Mvc\Dispatcher,
    Phalcon\Acl,
    Phalcon\Acl\Role;

/**
 * Security
 *
 * This is the security plugin which controls that users only have access to the modules they're assigned to
 */
class Security extends Plugin
{

        public function __construct($dependencyInjector)
        {
                $this->_dependencyInjector = $dependencyInjector;
        }

        public function getAcl()
        {
                if (!isset($this->persistent->acl)) {

                        $acl = new Phalcon\Acl\Adapter\Memory();

                        $acl->setDefaultAction(Phalcon\Acl::DENY);

                        // 
                        // Define role map. 
                        // 
                        $roles = array(
                                'admin'       => '*',
                                'teacher'     => array(
                                        'exam' => '*'
                                ),
                                'creator'     => array(
                                        'exam'        => '*',
                                        'contributor' => '*',
                                        'decoder'     => '*',
                                        'invigilator' => '*',
                                        'question'    => 'read',
                                        'topics'      => '*',
                                        'student'     => 'read'
                                ),
                                'contributor' => array(
                                        'exam'     => 'read',
                                        'question' => '*',
                                        'topics'   => 'read'
                                ),
                                'invigilator' => array(
                                        'exam'        => 'change',
                                        'invigilator' => 'read',
                                        'student'     => '*',
                                        'lock'        => '*',
                                        'computer'    => 'read',
                                        'room'        => 'read'
                                ),
                                'decoder'     => array(
                                        'exam'     => 'change',
                                        'student'  => 'read',
                                        'answer'   => 'read',
                                        'result'   => 'read',
                                        'question' => 'read',
                                        'topics'   => 'read'
                                ),
                                'corrector'   => array(
                                        'exam'     => 'read',
                                        'question' => 'read',
                                        'topic'    => 'read',
                                        'student'  => 'read',
                                        'answer'   => 'read',
                                        'result'   => '*'
                                ),
                                'student'     => array(
                                        'exam'     => 'read',
                                        'question' => 'read',
                                        'topic'    => 'read',
                                        'answer'   => '*'
                                )
                        );

                        // 
                        // Permissions map:
                        // 
                        $permissions = array(
                                '*'      => '*',
                                'read'   => 'read',
                                'change' => array('create', 'read', 'update'),
                                'full'   => array('create', 'read', 'update', 'delete')
                        );

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
                        
                        $this->persistent->acl = $acl;
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
        public function allowed($role, $resource, $action)
        {
                return $this->getAcl()->isAllowed($role, $resource, $action);
        }

}