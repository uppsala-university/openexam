<?php

// 
// The source code is copyrighted, with equal shared rights, between the
// authors (see the file AUTHORS) and the OpenExam project, Uppsala University 
// unless otherwise explicit stated elsewhere.
// 
// File:    AjaxController.php
// Created: 2014-08-20 11:36:22
// 
// Author:  Anders Lövgren (QNET/BMC CompDept)
// 

namespace OpenExam\Controllers\Core;

use OpenExam\Controllers\ServiceController;
use OpenExam\Library\Core\Handler\CoreHandler;
use OpenExam\Library\Core\Handler\Exception;
use OpenExam\Library\Security\Capabilities;
use OpenExam\Plugins\Security\Model\ObjectAccess;

/**
 * AJAX controller for core service.
 * 
 * The request input contains the encoding specific representation of an 
 * array with an optional data and params section. If data and params section 
 * is missing, then it's assumed to be data.
 * 
 * <code>
 * // Use array as data:
 * $input = array(...);
 * 
 * // Explicit define array as data:
 * $input = array('data' => array(...));
 * 
 * // Request without data:
 * $input = array('params' => array(...));
 * 
 * // Both data and query params:
 * $input = array(
 *      'data' => array(...), 'params' => array(...)
 * );
 * </code>
 * 
 * CRUD (create, read, update, delete) operations:
 * -----------------------------------------------------
 * 
 * The params part can contain any data supported by the query builder:
 * <code>
 * $params = array(
 *         'columns'    => array('id', 'name', 'status'),
 *         'conditions' => array(
 *                 array(
 *                         "created > :min: AND created < :max:",
 *                         array("min" => '2013-01-01', 'max' => '2014-01-01'),
 *                         array("min" => PDO::PARAM_STR, 'max' => PDO::PARAM_STR),
 *                 ),
 *         ),
 *         // or 'conditions' => "created > '2013-01-01' AND created < '2014-01-01'",
 *         'group'      => array('id', 'name'),
 *         'having'     => "name = 'Kamil'",
 *         'order'      => array('name', 'id'),
 *         'limit'      => 20,
 *         'offset'     => 20,
 * );
 * </code>
 * 
 * Input data encoding examples for jQuery POST:
 * <code>
 * // Read single object:
 * input: {"id":56}
 * 
 * // Same as previous:
 * input: {"data":{"id":56}}
 * 
 * // Pagination example:
 * input: {"params":{"limit":[1,0]}}
 * 
 * // Somewhat more complex:
 * input: {"params":{"columns":["id","name","status"],"conditions":[["created > :min: AND created < :max:",{"min":"2013-01-01","max":"2014-01-01"},{"min":2,"max":2}]],"group":["id","name"],"having":"name = 'Kamil'","order":["name","id"],"limit":20,"offset":20}}
 * </code>
 * 
 * To dynamic check capability to perform an action, pass a capability 
 * definition in the params. The capability field can be mixed with ordinary 
 * query/filter params:
 * 
 * <code>
 * // Perform all capability checks (same):
 * input: {"data":{...}:"params":{...,"capability":true}}
 * input: {"data":{...}:"params":{...,"capability":"all"}}
 * input: {"data":{...}:"params":{...,"capability":["all"]}}
 * 
 * // Perform the selected capability checks:
 * input: {"data":{...}:"params":{...,"capability":["static","role","action"]}}
 * </code>
 * 
 * Capabilities (static rules):
 * -----------------------------------------------------
 * 
 * Static capability maps can be queried by passing zero or more of the 
 * requested checks (role, resource and action):
 * 
 * // Get resources accessable by student role:
 * input: {"params":{"role":"student"}}
 * 
 * // Get roles with access to exam resource:
 * input: {"params":{"resource":"exam"}}
 * 
 * // Check if action is static allowed:
 * input: {"params":{"role":"student","resource":"exam","action":"read"}}
 * 
 * // Get all capabilities grouped by role (same):
 * input: {"params":{}}
 * 
 * Filtering result set:
 * -----------------------------------------------------
 * 
 * It's possible to filter result set (after query). It requires that the
 * model class has been prepared to handle attribute filtering. 
 * 
 * For example, the resultset from Exam::find(...) can be filtered by passing
 * a params filter that triggers the getFilter() to return a filter object:
 * 
 * // These are all equivalent:
 * input: {"params":{"state":64}}               // filter on state
 * input: {"params":{"flags":"upcoming"}}       // filter on single flag
 * input: {"params":{"flags":["upcoming"]}}     // filter on multiple flags
 * 
 * @see http://docs.phalconphp.com/en/latest/api/Phalcon_Mvc_Model_Query_Builder.html
 * @author Anders Lövgren (QNET/BMC CompDept)
 */
class AjaxController extends ServiceController
{

        /**
         * Success response tag.
         */
        const SUCCESS = 'success';
        /**
         * failure response tag.
         */
        const FAILURE = 'failed';

        /**
         * The capabilities mapper.
         * @var Capabilities 
         */
        private $capabilities;

        public function initialize()
        {
                $this->capabilities = new Capabilities(require(CONFIG_DIR . '/access.def'));
                parent::initialize();
        }

        /**
         * Display documentation of the AJAX service API.
         */
        public function apiAction()
        {
                // TODO: use view for displaying API docs

                $content = array(
                        "usage"   => array(
                                "/core/ajax/{role}/{model}/{action}" => "POST"
                        ),
                        "example" => array(
                                "/core/ajax/student/exam/create"     => "Create exam",
                                "/core/ajax/student/exam/read"       => "Read exam",
                                "/core/ajax/student/exam/update"     => "Update exam",
                                "/core/ajax/student/exam/delete"     => "Delete exam",
                                "/core/ajax/student/exam/capability" => "Get static capabilities"
                        )
                );

                $this->response->setJsonContent($content);
                $this->response->send();
        }

        /**
         * Perform create operation.
         * @param string $role The requested role.
         * @param string $type The requested model.
         */
        public function createAction($role, $type)
        {
                $this->crudAction($role, $type, ObjectAccess::CREATE);
        }

        /**
         * Perform read operation.
         * @param string $role The requested role.
         * @param string $type The requested model.
         */
        public function readAction($role, $type)
        {
                $this->crudAction($role, $type, ObjectAccess::READ);
        }

        /**
         * Perform update operation.
         * @param string $role The requested role.
         * @param string $type The requested model.
         */
        public function updateAction($role, $type)
        {
                $this->crudAction($role, $type, ObjectAccess::UPDATE);
        }

        /**
         * Perform delete operation.
         * @param string $role The requested role.
         * @param string $type The requested model.
         */
        public function deleteAction($role, $type)
        {
                $this->crudAction($role, $type, ObjectAccess::DELETE);
        }

        /**
         * Get input (model) data and params from request.
         * @return array
         * @throws Exception
         */
        private function getInput()
        {
                // 
                // Payload is either on stdin or in POST-data:
                // 
                if (count($this->request->getPost()) > 0) {
                        $input = $this->request->getPost();
                } else {
                        $input = file_get_contents("php://input");
                }

                // 
                // Convert data if needed/requested:
                // 
                if (is_string($input)) {
                        if ($this->request->getBestAccept() == 'application/json') {
                                $input = json_decode($input, true);
                        } elseif (($temp = json_decode($input, true)) != null) {
                                $input = $temp;
                        }
                        if (!isset($input)) {
                                throw new Exception("Unhandled content type");
                        }
                }

                if (!isset($input)) {
                        throw new Exception("Input data is missing");
                }

                // 
                // Currently, we are only handling array data;
                // 
                if (!is_array($input)) {
                        $input = (array) $input;
                }

                // 
                // Separate on model data and query params:
                // 
                foreach (array('data', 'params') as $part) {
                        if (isset($input[$part])) {
                                $$part = (array) $input[$part];
                        }
                }

                // 
                // Assume non-empty input is data by default:
                // 
                if (!isset($data) && !isset($params) && key($input) != "0") {
                        $data = $input;
                }
                if (!isset($data)) {
                        $data = array();
                }
                if (!isset($params)) {
                        $params = array();
                }

                return array($data, $params);
        }

        /**
         * Send result to peer.
         * @param string $status The status label.
         * @param mixed $result The operation result.
         */
        private function sendResponse($status, $result)
        {
                $this->response->setJsonContent(array($status => $result));
                $this->response->send();
        }

        /**
         * Perform CRUD operation.
         * @param string $role The requested role.
         * @param string $type The requested model.
         * @param string $action The requested action.
         * @throws Exception
         */
        private function crudAction($role, $type, $action)
        {
                $result = array();
                $models = array();

                try {
                        // 
                        // Get request input:
                        // 
                        list($data, $params) = $this->getInput();

                        // 
                        // Static check if capabilities allow this action:
                        // 
                        if (!isset($params['capability'])) {
                                if ($this->capabilities->hasPermission($role, $type, $action) == false) {
                                        return $this->sendResponse(self::FAILURE, _("You don't have permissions to perform this action."));
                                }
                        }

                        // 
                        // Handler request thru core handler:
                        // 
                        $handler = new CoreHandler($role);

                        // 
                        // Handle single or multiple models:
                        // 
                        if (is_numeric(key($data))) {
                                foreach ($data as $d) {
                                        $models[] = $handler->build($type, (array) $d);
                                }
                        } else {
                                $models[] = $handler->build($type, $data);
                        }

                        // 
                        // Handle dynamic capability checks:
                        // 
                        if (isset($params['capability'])) {
                                $filter = Capabilities::getFilter($params['capability']);
                                foreach ($models as $model) {
                                        if (($result = $this->capabilities->hasCapability($model, $action, $filter)) == false) {
                                                break;
                                        }
                                }
                                return $this->sendResponse(self::SUCCESS, $result);
                        }

                        // 
                        // Perform action on model(s):
                        // 
                        $this->sendResponse(self::SUCCESS, $handler->action($models, $action, $params));
                } catch (\Exception $exception) {
                        $this->sendResponse(self::FAILURE, $exception->getMessage());
                }
        }

        /**
         * Handle static capability checks.
         */
        public function capabilityAction()
        {
                list($data, $params) = $this->getInput();
                $filter = array(
                        'role'     => false,
                        'resource' => false,
                        'action'   => false
                );
                $filter = array_merge($filter, $params);

                if ($filter['role'] && $filter['resource'] && $filter['action']) {
                        $result = $this->capabilities->hasPermission($filter['role'], $filter['resource'], $filter['action']);
                } elseif ($filter['role'] && $filter['resource']) {
                        $result = $this->capabilities->getPermissions($filter['role'], $filter['resource']);
                } elseif ($filter['role']) {
                        $result = $this->capabilities->getResources($filter['role']);
                } elseif ($filter['resource']) {
                        $result = $this->capabilities->getRoles($filter['resource']);
                } else {
                        $result = $this->capabilities->getCapabilities();
                }


                $this->sendResponse(self::SUCCESS, $result);
        }

}
