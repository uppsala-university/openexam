<?php

// 
// The source code is copyrighted, with equal shared rights, between the
// authors (see the file AUTHORS) and the OpenExam project, Uppsala University 
// unless otherwise explicit stated elsewhere.
// 
// File:    Capabilities.php
// Created: 2014-10-16 10:50:11
// 
// Author:  Anders Lövgren (Computing Department at BMC, Uppsala University)
// 

namespace OpenExam\Library\Security;

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
class Capabilities
{

        /**
         * Role to resource permission map.
         * @var array 
         */
        private $rolecap = array();
        /**
         * Resource to role permission map.
         * @var array 
         */
        private $rescap = array();
        /**
         * Discovered resources.
         * @var array 
         */
        private static $resources = array();

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
                // Collect all defined resources:
                // 
                foreach ($access['roles'] as $role => $resources) {
                        if (is_array($resources)) {
                                foreach (array_keys($resources) as $resource) {
                                        if (!in_array($resource, self::$resources)) {
                                                self::$resources[] = $resource;
                                        }
                                }
                        }
                }

                // 
                // Create capability maps:
                // 
                foreach ($access['roles'] as $role => $resources) {
                        if (is_string($resources)) {
                                foreach (self::$resources as $resource) {
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
                $this->rolecap[$role][$resource] = $actions;
                $this->rescap[$resource][$role] = $actions;
        }

        /**
         * Get roles having permissions on resource.
         * @param string $resource
         * @return boolean|array
         */
        public function getRoles($resource)
        {
                if (isset($this->rescap[$resource])) {
                        return $this->rescap[$resource];
                } else {
                        return false;
                }
        }

        /**
         * Get permitted resources for role.
         * @param string $role The role name.
         * @return boolean|array
         */
        public function getResources($role)
        {
                if (isset($this->rolecap[$role])) {
                        return $this->rolecap[$role];
                } else {
                        return false;
                }
        }

        /**
         * Get permitted actions on resource accessed using role.
         * @param string $role The role name.
         * @param string $resource The resource name.
         * @return boolean|array
         */
        public function getPermissions($role, $resource)
        {
                if (isset($this->rolecap[$role][$resource])) {
                        return $this->rolecap[$role][$resource];
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
                if (!isset($this->rolecap[$role][$resource])) {
                        return false;
                } else {
                        return in_array($action, $this->rolecap[$role][$resource]);
                }
        }

        /**
         * Get all capabilities grouped by role.
         * @return array
         */
        public function getCapabilities()
        {
                return $this->rolecap;
        }

}
