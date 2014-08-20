<?php

use Phalcon\Events\Event,
    Phalcon\Mvc\User\Plugin,
    Phalcon\Mvc\Dispatcher,
    Phalcon\Acl;

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

                        //Register roles
                        $roles = array(
                                'staff'   => new Phalcon\Acl\Role('Staff'),
                                'student' => new Phalcon\Acl\Role('Student'),
                                'admin'   => new Phalcon\Acl\Role('Admin'),
                                'guest'   => new Phalcon\Acl\Role('Guest'),
                        );
                        foreach ($roles as $role) {
                                $acl->addRole($role);
                        }

                        //Private area resources
                        $privateResources = array(
                                'exam'     => array('index', 'search', 'new', 'edit', 'save', 'create', 'delete'),
                                'question' => array('index', 'search', 'new', 'edit', 'save', 'create', 'delete')
                        );
                        foreach ($privateResources as $resource => $actions) {
                                $acl->addResource(new Phalcon\Acl\Resource($resource), $actions);
                        }

                        //Public area resources
                        $publicResources = array(
                                'index'   => array('index'),
                                'about'   => array('index'),
                                'contact' => array('index', 'send')
                        );
                        foreach ($publicResources as $resource => $actions) {
                                $acl->addResource(new Phalcon\Acl\Resource($resource), $actions);
                        }

                        //Grant access to public areas to both restricted users and guests
                        foreach ($roles as $role) {
                                foreach ($publicResources as $resource => $actions) {
                                        $acl->allow($role->getName(), $resource, '*');
                                }
                        }

                        //Grant acess to *all* private area to role Admin
                        foreach ($privateResources as $resource => $actions) {
                                foreach ($actions as $action) {
                                        $acl->allow('Admin', $resource, $action);
                                }
                        }

                        //The acl is stored in session, APC can be useful here too
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

}
