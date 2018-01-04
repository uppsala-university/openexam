<?php

/*
 * Copyright (C) 2015-2018 The OpenExam Project
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
 */

// 
// File:    CoreHandler.php
// Created: 2015-02-04 13:29:03
// 
// Author:  Anders Lövgren (Computing Department at BMC, Uppsala University)
// 

namespace OpenExam\Library\WebService\Handler;

use Exception;
use OpenExam\Library\Core\Handler\CoreHandler as BackendHandler;
use OpenExam\Library\Security\Capabilities;
use OpenExam\Library\Security\Exception as SecurityException;
use OpenExam\Library\Security\User;
use OpenExam\Library\WebService\Common\ServiceHandler;
use OpenExam\Library\WebService\Common\ServiceRequest;
use OpenExam\Library\WebService\Common\ServiceResponse;
use OpenExam\Plugins\Security\Model\ObjectAccess;

/**
 * Core service handler.
 * 
 * This class provides CRUD (create, read, update and delete) operations
 * against all models.
 *
 * @author Anders Lövgren (Computing Department at BMC, Uppsala University)
 */
class CoreHandler extends ServiceHandler
{

        /**
         * @var Capabilities 
         */
        private $_capabilities;

        /**
         * Constructor.
         * @param ServiceRequest $request The service request.
         * @param User $user The logged in user.
         * @param Capabilities $capabilities
         */
        public function __construct($request, $user, $capabilities)
        {
                parent::__construct($request, $user);
                $this->_capabilities = $capabilities;
        }

        /**
         * Perform create operation.
         * @param string $role The requested role.
         * @param string $type The requested model.
         * @return ServiceResponse 
         */
        public function create($role, $type)
        {
                return $this->action($role, $type, ObjectAccess::CREATE);
        }

        /**
         * Perform read operation.
         * @param string $role The requested role.
         * @param string $type The requested model.
         * @return ServiceResponse 
         */
        public function read($role, $type)
        {
                return $this->action($role, $type, ObjectAccess::READ);
        }

        /**
         * Perform update operation.
         * @param string $role The requested role.
         * @param string $type The requested model.
         * @return ServiceResponse 
         */
        public function update($role, $type)
        {
                return $this->action($role, $type, ObjectAccess::UPDATE);
        }

        /**
         * Perform delete operation.
         * @param string $role The requested role.
         * @param string $type The requested model.
         * @return ServiceResponse 
         */
        public function delete($role, $type)
        {
                return $this->action($role, $type, ObjectAccess::DELETE);
        }

        /**
         * Perform the CRUD operation.
         * @param string $role The requested role.
         * @param string $type The requested model.
         * @param string $action The requested action.
         * @throws Exception
         * @return ServiceResponse 
         */
        private function action($role, $type, $action)
        {
                $result = array();
                $models = array();

                // 
                // Set primary role before capabilities check for better error
                // reporting.
                // 
                $this->_user->setPrimaryRole($role);

                // 
                // Static check if capabilities allow this action:
                // 
                if (!isset($this->_request->params['capability'])) {
                        if ($this->_capabilities->hasPermission($role, $type, $action) == false) {
                                throw new SecurityException("You don't have permissions to perform this action.", SecurityException::ACTION);
                        }
                }

                // 
                // Handler request thru core handler:
                // 
                $handler = new BackendHandler($role);

                // 
                // Handle single or multiple models:
                // 
                if (is_numeric(key($this->_request->data))) {
                        foreach ($this->_request->data as $data) {
                                $models[] = $handler->build($type, (array) $data);
                        }
                } else {
                        $models[] = $handler->build($type, $this->_request->data);
                }

                // 
                // Perform requested action:
                // 
                if (isset($this->_request->params['capability'])) {
                        // 
                        // Handle dynamic capability checks:
                        // 
                        $filter = Capabilities::getFilter($this->_request->params['capability']);
                        foreach ($models as $model) {
                                if (($result = $this->_capabilities->hasCapability($model, $action, $filter)) == false) {
                                        break;
                                }
                        }
                        return new ServiceResponse($this, self::SUCCESS, $result);
                } else {
                        // 
                        // Perform action on model(s):
                        // 
                        $result = $handler->action($models, $action, $this->_request->params);
                        return new ServiceResponse($this, self::SUCCESS, $result);
                }
        }

        /**
         * Handle static capability checks.
         * @param array $filter The default capability filter.
         * @return ServiceResponse 
         */
        public function capability($filter = array(
                'role'     => false,
                'resource' => false,
                'action'   => false
        ))
        {
                $filter = array_merge($filter, $this->_request->params);

                if ($filter['role'] && $filter['resource'] && $filter['action']) {
                        $result = $this->_capabilities->hasPermission($filter['role'], $filter['resource'], $filter['action']);
                } elseif ($filter['role'] && $filter['resource']) {
                        $result = $this->_capabilities->getPermissions($filter['role'], $filter['resource']);
                } elseif ($filter['role']) {
                        $result = $this->_capabilities->getResources($filter['role']);
                } elseif ($filter['resource']) {
                        $result = $this->_capabilities->getRoles($filter['resource']);
                } else {
                        $result = $this->_capabilities->getCapabilities();
                }

                return new ServiceResponse($this, self::SUCCESS, $result);
        }

}
