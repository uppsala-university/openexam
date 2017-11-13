<?php

// 
// The source code is copyrighted, with equal shared right', between the
// authors (see the file AUTHORS) and the OpenExam project, Uppsala University 
// unless otherwise explicit stated elsewhere.
// 
// File:    Capabilities.php
// Created: 2014-10-16 10:50:11
// 
// Author:  Anders Lövgren (Computing Department at BMC, Uppsala University)
// 

namespace OpenExam\Library\Security;

use Exception;
use InvalidArgumentException;
use OpenExam\Models\ModelBase;
use Phalcon\Mvc\User\Component;

/**
 * Capability check for resource.
 * @author Anders Lövgren (QNET)
 */
class Capability extends Component
{

        /**
         * The resource name.
         * @var string
         */
        private $_resource;
        /**
         * The model object. 
         * @var ModelBase 
         */
        private $_model;
        /**
         * The check filter.
         * @var int 
         */
        private $_filter;

        /**
         * Constructor.
         * @param string|ModelBase $model The model object.
         */
        public function __construct($model)
        {
                if (is_string($model)) {
                        $this->_model = null;
                        $this->_resource = $model;
                        $this->_filter = Capabilities::CHECK_STATIC;
                } else {
                        $this->_model = $model;
                        $this->_resource = $model->getResourceName();
                        $this->_filter = Capabilities::CHECK_ALL;
                }
        }

        /**
         * Set model filter.
         * 
         * @param mixed $filter The checks to perform.
         * @throws InvalidArgumentException
         */
        public function setFilter($filter)
        {
                if ($filter < Capabilities::CHECK_MIN ||
                    $filter > Capabilities::CHECK_MAX) {
                        throw new InvalidArgumentException("Filter outside range");
                }

                $this->_filter = $filter;
        }

        /**
         * Check if caller can perform action.
         * 
         * @param string $action The requested action.
         * @return bool
         */
        public function canPerform($action, $role = null)
        {
                if (!isset($this->_model)) {
                        $this->_filter = Capabilities::CHECK_STATIC;
                }

                if ($this->_filter & Capabilities::CHECK_STATIC) {
                        if (!$this->checkStatic($action, $role)) {
                                return false;
                        }
                }
                if ($this->_filter & Capabilities::CHECK_ROLE) {
                        if (!$this->checkObjectRole($action)) {
                                return false;
                        }
                }
                if ($this->_filter & Capabilities::CHECK_ACTION) {
                        if (!$this->checkObjectAction($action)) {
                                return false;
                        }
                }

                return true;
        }

        private function checkStatic($action, $role)
        {
                $check = $this->capabilities->getRoles($this->_resource);

                foreach ($check as $r => $a) {
                        if (isset($role) && $role != $r) {
                                continue;
                        }
                        if (!in_array($action, $a)) {
                                continue;
                        }
                        if ($this->user->roles->acquire($r)) {
                                return true;
                        }
                }

                return false;
        }

        private function checkObjectRole($action)
        {
                $access = $this->_model->getObjectAccess();
                return $access->checkObjectRole($action, $this->_model, $this->user);
        }

        private function checkObjectAction($action)
        {
                $access = $this->_model->getObjectAccess();
                return $access->checkObjectAction($action, $this->_model, $this->user);
        }

}

/**
 * Collects capabilities from access list.
 * 
 * This class can be used to provide static access control based on role,
 * action and requested resource (e.g. to decide on which pages to display
 * in the web interface):
 * 
 * <code>
 * $capabilities = new Capabilities(require(CONFIG_DIR . '/access.def'));
 * 
 * if ($capabilities->hasPermission($role, $resource, 'read')) {
 *      // OK, static view access granted.
 * }
 * </code>
 * 
 * Other use cases exists, for example to generate REST API documentation:
 * 
 * <code>
 * $capabilities = new Capabilities(require(CONFIG_DIR . '/access.def'));
 * 
 * foreach ($capabilities->getResources() as $role => $resources) {
 *      foreach ($resources as $resource => $actions) {
 *              $this->showHttpOptions($role, $resource, $actions);
 *      }
 * }
 * </code>
 * @author Anders Lövgren (Computing Department at BMC, Uppsala University)
 */
class Capabilities extends Component
{

        const CHECK_MIN = 0;
        const CHECK_MAX = 7;
        /**
         * Perform static check against access control list (ACL).
         */
        const CHECK_STATIC = 1;
        /**
         * Perform role control on model object.
         */
        const CHECK_ROLE = 2;
        /**
         * Perform action  control on model object.
         */
        const CHECK_ACTION = 4;
        /**
         * Perform all model action controls (CHECK_STATIC | CHECK_ROLE | CHECK_ACTION).
         */
        const CHECK_ALL = self::CHECK_MAX;
        /**
         * Don't perform any model action checks.
         */
        const CHECK_NONE = 0;

        /**
         * Role to resource permission map.
         * @var array 
         */
        private $_rolecap = array();
        /**
         * Resource to role permission map.
         * @var array 
         */
        private $_rescap = array();
        /**
         * All managable resources.
         * @var array 
         */
        private static $_resources = array(
                'admin', 'answer', 'computer', 'contributor', 'corrector',
                'decoder', 'exam', 'file', 'invigilator', 'lock', 'question',
                'resource', 'result', 'room', 'student', 'teacher', 'topic'
        );

        /**
         * Constructor.
         * @param array $access The permission array.
         */
        public function __construct($access = array())
        {
                $this->initialize($access);
        }

        /**
         * Initialize capability mapping.
         * @param array $access The access array.
         */
        private function initialize($access)
        {
                $permissions = array();

                // 
                // Create action lookup table:
                // 
                foreach ($access['permissions'] as $name => $action) {
                        if (is_array($action)) {
                                $permissions[$name] = $action;
                        } elseif ($action == '*') {
                                $permissions[$name] = $access['permissions']['full'];
                        } else {
                                $permissions[$name] = array($action);
                        }
                }

                // 
                // Create capability maps:
                // 
                foreach ($access['roles'] as $role => $resources) {
                        if (is_string($resources)) {
                                foreach (self::$_resources as $resource) {
                                        $this->addCapabilities($role, $resource, $permissions[$resources]);
                                }
                        } elseif (is_array($resources)) {
                                foreach ($resources as $resource => $permission) {
                                        $this->addCapabilities($role, $resource, $permissions[$permission]);
                                }
                        }
                }
        }

        /**
         * Add capabilities (permitted actions) for role on resource.
         * @param string $role The role name.
         * @param string $resource The resource name.
         * @param array $actions The permitted actions.
         */
        private function addCapabilities($role, $resource, $actions)
        {
                $this->_rolecap[$role][$resource] = $actions;
                $this->_rescap[$resource][$role] = $actions;
        }

        /**
         * Get roles having permissions on resource.
         * 
         * If $resource is unset, then this method returns all defined roles.
         * If the resource is not defined, then false is returned.
         * 
         * @param string $resource
         * @return boolean|array
         */
        public function getRoles($resource = null)
        {
                if (!isset($resource)) {
                        return array_keys($this->_rolecap);
                } elseif (isset($this->_rescap[$resource])) {
                        return $this->_rescap[$resource];
                } else {
                        return false;
                }
        }

        /**
         * Get permitted resources for role.
         * 
         * If $role is unset, then this method returns all defined resources. 
         * If the role is not defined, then false is returned.
         * 
         * @param string $role The role name.
         * @return boolean|array
         */
        public function getResources($role = null)
        {
                if (!isset($role)) {
                        return array_keys($this->_rescap);
                } elseif (isset($this->_rolecap[$role])) {
                        return $this->_rolecap[$role];
                } else {
                        return false;
                }
        }

        /**
         * Check if resource exist in resources list.
         * @param string $resource The resource name.
         * @return boolean
         */
        public function hasResource($resource)
        {
                return in_array($resource, $this->getResources());
        }

        /**
         * Get permitted actions on resource accessed using role.
         * @param string $role The role name.
         * @param string $resource The resource name.
         * @return boolean|array
         */
        public function getPermissions($role, $resource)
        {
                if (isset($this->_rolecap[$role][$resource])) {
                        return $this->_rolecap[$role][$resource];
                } else {
                        return false;
                }
        }

        /**
         * Check if role has permission to perform action on resource.
         * @param string $role
         * @param string $resource
         * @param string $action
         * @return boolean
         */
        public function hasPermission($role, $resource, $action)
        {
                if (!isset($this->_rolecap[$role][$resource])) {
                        return false;
                } else {
                        return in_array($action, $this->_rolecap[$role][$resource]);
                }
        }

        /**
         * Check if caller has permission to perform action on model object.
         * 
         * This method checks if performing requested action on model object
         * would succeed without actually performing the action. 
         * 
         * The $filter argument defines which checks to perform. If $filter
         * includes CHECK_STATIC, then $this->hasPermission() is called. If
         * $filter includes CHECK_ROLE and CHECK_ACTION, then the role and
         * action is checked on the model object.
         *
         * Notice that these two method call are equivalent:
         * 
         * <code>
         * $role = $this->user->getPrimaryRole();
         * $name = $model->getResourceName();
         * $action = ObjectAccess::READ;
         * 
         * $capabilities->hasCapability($model, $access, CHECK_STATIC);
         * $capabilities->hasPermission($role, $name, $access);
         * </code>
         * 
         * @param string|ModelBase $model The model object.
         * @param string $action The requested action.
         * @param mixed $filter The checks to perform.
         * @return bool True if action is allowed.
         */
        public function hasCapability($model, $action, $filter = self::CHECK_ALL)
        {
                try {
                        $capability = new Capability($model);
                        $capability->setFilter($filter);
                        return $capability->canPerform($action);
                } catch (Exception $ex) {
                        return false;
                }
        }

        /**
         * Get all capabilities grouped by role.
         * @return array
         */
        public function getCapabilities()
        {
                return $this->_rolecap;
        }

        /**
         * Get bitmask from $filter argument.
         * 
         * <code>
         * $filter = Capabilities::getFilter(array('static','role','action');
         * $filter = Capabilities::getFilter(array('all');
         * $filter = Capabilities::getFilter(true);
         * $filter = Capabilities::getFilter(true);
         * $filter = Capabilities::getFilter(true);
         * </code>
         * 
         * @param array|int|bool|string $filter The filter argument.
         * @return int
         */
        public static function getFilter($filter)
        {
                $result = 0;
                $lookup = array(
                        'all'    => self::CHECK_ALL,
                        'static' => self::CHECK_STATIC,
                        'role'   => self::CHECK_ROLE,
                        'action' => self::CHECK_ACTION
                );

                if (is_string($filter)) {
                        $filter = $lookup[$filter];
                }

                if (is_bool($filter)) {
                        return $filter ? self::CHECK_ALL : self::CHECK_NONE;
                }

                if (is_int($filter)) {
                        if ($filter < self::CHECK_MIN) {
                                return self::CHECK_NONE;
                        } elseif ($filter > self::CHECK_MAX) {
                                return self::CHECK_MAX;
                        } else {
                                return $filter;
                        }
                }

                if (is_array($filter)) {
                        foreach ($filter as $key) {
                                $result |= $lookup[$key];
                        }
                        return $result;
                }
        }

}
