<?php

// 
// The source code is copyrighted, with equal shared rights, between the
// authors (see the file AUTHORS) and the OpenExam project, Uppsala University 
// unless otherwise explicit stated elsewhere.
// 
// File:    CoreController.php
// Created: 2014-08-20 11:35:41
// 
// Author:  Anders Lövgren (QNET/BMC CompDept)
// 

namespace OpenExam\Controllers\Service\Rest;

use OpenExam\Controllers\Service\RestController;
use OpenExam\Library\WebService\Common\Exception as ServiceException;
use OpenExam\Library\WebService\Common\ServiceHandler;
use OpenExam\Library\WebService\Common\ServiceRequest;
use OpenExam\Library\WebService\Common\ServiceResponse;
use OpenExam\Library\WebService\Handler\CoreHandler;
use OpenExam\Plugins\Security\Model\ObjectAccess;

/**
 * REST controller for core service.
 * 
 * Provides CRUD operations and search functionality using REST methods. Use
 * these HTTP mehods:
 * 
 * <ul>
 * <li>GET to browse collections or display objects.</li>
 * <li>POST for creating objects.</li>
 * <li>PUT to update objects.</li>
 * <li>DELETE for deleting objects.</li>
 * </ul>
 * 
 * Input data (for POST or PUT) should be JSON encoded. The response is 
 * JSON encoded for array data results.
 * 
 * Browsing:
 * ------------------
 * Use the exams or questions collections as startpoint. The selected role
 * filters collection objects (e.g. exam or question objects):
 * 
 * // Show all accessable exams:
 * curl -XGET ${BASEURL}/rest/core/creator/exams
 * 
 * // Show all accessable questions:
 * curl -XGET ${BASEURL}/rest/core/corrector/questions
 * 
 * Select an object to explore itself or related collections:
 * 
 * // Get exam 312:
 * curl -XGET ${BASEURL}/rest/core/corrector/exams/312
 * 
 * // Get questions on exam 312:
 * curl -XGET ${BASEURL}/rest/core/corrector/exams/312/questions
 * 
 * // Get all answers to question 171:
 * curl -XGET ${BASEURL}/rest/core/corrector/exams/312/questions/171/answers
 * 
 * Modify objects:
 * ------------------
 * 
 * Objects are modified using POST (create), PUT (update) or DELETE:
 * 
 * // Create new exam. The exam data is in the payload (-d):
 * curl -XPOST ${BASEURL}/rest/core/teacher/exams -d '{...}'
 * 
 * // Update an existing exam. The data is in the payload (-d):
 * curl -XPUT ${BASEURL}/rest/core/teacher/exams/312 -d '{...}'
 * 
 * // Delete exam 312:
 * curl -XDELETE ${BASEURL}/rest/core/creator/exams/312
 * 
 * Searching:
 * ------------------
 * 
 * Search is performed by posting search options to the {role}/search/{target} 
 * action handler. Any parameters supported by Phalcon's query builder can
 * be used:
 * 
 * <code>
 * $params = json_encode(array(
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
 * ));
 * </code>
 * 
 * // Search for all questions containing 'tricky':
 * curl -XPOST ${BASEURL}/rest/core/creator/questions/search -d \
 *      '{"data":{"name":"tricky"}}'
 * 
 * // The number of items in response can be inlined:
 * curl -XPOST ${BASEURL}/rest/core/creator/questions/search -d \
 *      '{"data":{"name":"tricky"},"params":{"count":"inline"}}'
 * 
 * // Searching for upcoming exams in model attributes:
 * curl -XPOST ${BASEURL}/rest/core/creator/exams/search -d \
 *      '{"params":{"flags":["upcoming"]}}'
 * 
 * Capabilities (static rules):
 * -----------------------------------------------------
 * 
 * Static capability maps can be queried by passing zero or more of the 
 * requested checks (role, resource and action):
 * 
 * // Get resources accessable by student role:
 * curl -XPOST ${BASEURL}/rest/core/creator/capability -d {"params":{"role":"student"}}
 * 
 * // Get roles with access to exam resource:
 * curl -XPOST ${BASEURL}/rest/core/creator/capability -d {"params":{"resource":"exam"}}
 * 
 * // Check if action is static allowed:
 * curl -XPOST ${BASEURL}/rest/core/creator/capability -d {"params":{"role":"student","resource":"exam","action":"read"}}
 * 
 * // Get all capabilities grouped by role (same):
 * curl -XPOST ${BASEURL}/rest/core/creator/capability -d {"params":{}}
 * 
 * @author Anders Lövgren (QNET/BMC CompDept)
 */
class CoreController extends RestController
{

        /**
         * @var CoreHandler
         */
        protected $handler;

        public function initialize()
        {
                parent::initialize();
                $this->handler = new CoreHandler($this->getRequest(), $this->user, $this->capabilities);
        }

        public function apiAction()
        {
                // TODO: use view for displaying API docs

                $content = array(
                        "usage"   => array(
                                "/core/rest/{role}/{target}"        => array("GET", "POST"),
                                "/core/rest/{role}/{target}/{id}"   => array("PUT", "DELETE"),
                                "/core/rest/{role}/{target}/search" => "POST"
                        ),
                        "example" => array(
                                "/core/rest/student/exams/44/questions/22/answers",
                                "/core/rest/student/exams/44/questions/22/answers/123",
                                "/core/rest/student/admins",
                                "/core/rest/student/teachers",
                                "/core/rest/student/rooms",
                                "/core/rest/student/exams/search"
                        )
                );

                $this->sendResponse(new ServiceResponse($this->handler, ServiceHandler::SUCCESS, $content));
        }

        /**
         * Handle core operations and search.
         * @param string $role The request role.
         * @param string $target The target resource.
         */
        public function indexAction($role = null, $target = null)
        {
                if (!isset($role)) {
                        throw new ServiceException("Invalid request (missing role)");
                }
                if (!isset($target)) {
                        throw new ServiceException("Invalid request (missing target)");
                }
                                
                $request = $this->handler->getRequest();

                switch ($request->action) {
                        case ObjectAccess::CREATE:
                                $this->sendResponse($this->handler->create($request->role, $request->model));
                                break;
                        case ObjectAccess::READ:
                                $this->sendResponse($this->handler->read($request->role, $request->model));
                                break;
                        case ObjectAccess::UPDATE:
                                $this->sendResponse($this->handler->update($request->role, $request->model));
                                break;
                        case ObjectAccess::DELETE:
                                $this->sendResponse($this->handler->delete($request->role, $request->model));
                                break;
                }
        }

        /**
         * Handle static capability checks.
         */
        public function capabilityAction()
        {
                $response = $this->handler->capability();
                $this->sendResponse($response);
        }

        /**
         * Get service request.
         * 
         * Specialized request mapper for core service. We need this because
         * this service is far more complex than other REST services in that
         * we support search functionality too.
         * 
         * @param callable $remapper
         * @return ServiceRequest
         */
        protected function getRequest($remapper = null)
        {
                $request = parent::getRequest();
                $params = $this->dispatcher->getParams();

                if(count($params) == 0) {
                        return $request;
                }

                // 
                // Map request method onto handler methods:
                // 
                switch ($this->request->getMethod()) {
                        case 'POST':
                                $request->action = ObjectAccess::CREATE;
                                break;
                        case 'GET':
                                $request->action = ObjectAccess::READ;
                                break;
                        case 'PUT':
                                $request->action = ObjectAccess::UPDATE;
                                break;
                        case 'DELETE':
                                $request->action = ObjectAccess::DELETE;
                                break;
                }

                // 
                // Special handling for search request:
                // 
                if (isset($params[0]) && $params[0] == "search") {
                        $request->action = ObjectAccess::READ;
                        array_shift($params);
                        unset($request->data['search']);
                }

                // 
                // Cleanup named parameters:
                // 
                foreach (array('role', 'target') as $name) {
                        if (isset($params[$name])) {
                                if (isset($request->data[$params[$name]])) {
                                        unset($request->data[$params[$name]]);
                                }
                                $request->$name = $params[$name];
                                unset($params[$name]);
                        }
                }

                array_unshift($params, $request->target);
                $params = array_reverse($params);

                // 
                // Map params onto model, primary and foreign keys:
                // 
                if (is_numeric($params[0])) {
                        $request->model = substr($params[1], 0, -1);
                        $request->data['id'] = $params[0];
                } else {
                        $request->model = substr($params[0], 0, -1);
                        if (isset($params[2])) {
                                $foreign = sprintf("%s_id", substr($params[2], 0, -1));
                                $request->data[$foreign] = $params[1];
                        }
                }

                // 
                // Remove numerical keys from input data:
                // 
                foreach (array_keys($request->data) as $id) {
                        if (is_numeric($id)) {
                                unset($request->data[$id]);
                        }
                }

                return $request;
        }

}
