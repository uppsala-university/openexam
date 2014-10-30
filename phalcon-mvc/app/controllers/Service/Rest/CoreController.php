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

use Exception;
use OpenExam\Controllers\ServiceController;
use OpenExam\Library\Core\Handler\CoreHandler;
use OpenExam\Library\Security\Exception as SecurityException;
use OpenExam\Plugins\Security\Model\ObjectAccess;

/**
 * REST request helper class.
 */
class RestRequest
{

        /**
         * The target collection.
         * @var string 
         */
        public $target;
        /**
         * The HTTP method.
         * @var string 
         */
        public $method;
        /**
         * The target action (e.g. create).
         * @var string 
         */
        public $action;
        /**
         * The action resource (e.g. exam or answer).
         * @var string 
         */
        public $model;
        /**
         * The invocation primary role.
         * @var string 
         */
        public $role;
        /**
         * Data for core action.
         * @var array 
         */
        public $data = array();
        /**
         * Params for core action.
         * @var array 
         */
        public $params = array();

        /**
         * Constructor.
         * @param string $method The HTTP method.
         * @param array $params The target collection and role.
         * @param array $payload The request payload (for POST/PUT only).
         */
        public function __construct($method, $params, $payload)
        {
                if (isset($params['role'])) {
                        $this->role = $params['role'];
                        unset($params['role']);
                }
                if (isset($params['target'])) {
                        $this->target = $params['target'];
                        unset($params['target']);
                }

                $this->method = $method;
                $this->action = $this->getAction();

                if ($this->method == 'POST' || $this->method == 'PUT') {
                        $this->data = $payload[0];
                        $this->params = $payload[1];
                }
                if ($this->method == 'GET' || $this->method == 'DELETE') {
                        $this->data = array();
                }

                array_unshift($params, $this->target);
                $params = array_reverse($params);

                // 
                // Map params onto model, primary and foreign keys:
                // 
                if (is_numeric($params[0])) {
                        $this->model = substr($params[1], 0, -1);
                        $this->data['id'] = $params[0];
                } else {
                        $this->model = substr($params[0], 0, -1);
                        if (isset($params[2])) {
                                $foreign = sprintf("%s_id", substr($params[2], 0, -1));
                                $this->data[$foreign] = $params[1];
                        }
                }
        }

        /**
         * Get action from HTTP method.
         * @return string
         */
        private function getAction()
        {
                switch ($this->method) {
                        case 'GET':
                                return 'read';
                        case 'POST':
                                return 'create';
                        case 'PUT':
                                return 'update';
                        case 'DELETE':
                                return 'delete';
                }
        }

}

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
 * curl -XPOST ${BASEURL}/rest/core/creator/search/questions -d \
 *      '{"data":{"name":"tricky"}'
 * 
 * // The number of items in response can be inlined:
 * curl -XPOST ${BASEURL}/rest/core/creator/search/questions -d \
 *      '{"data":{"name":"tricky"},"params":{"count":"inline"}}'
 * 
 * // Searching for upcoming exams in model attributes:
 * curl -XPOST ${BASEURL}/rest/core/creator/search/exams -d \
 *      '{"params":{"flags":["upcoming"]}}'
 * 
 * @author Anders Lövgren (QNET/BMC CompDept)
 */
class CoreController extends ServiceController
{

        public function apiAction()
        {
                // TODO: use view for displaying API docs

                $content = array(
                        "usage"   => array(
                                "/core/rest/{role}/{target}"        => array("GET", "POST"),
                                "/core/rest/{role}/{target}/{id}"   => array("PUT", "DELETE"),
                                "/core/rest/{role}/search/{target}" => "POST"
                        ),
                        "example" => array(
                                "/core/rest/student/exams/44/questions/22/answers",
                                "/core/rest/student/exams/44/questions/22/answers/123",
                                "/core/rest/student/admins",
                                "/core/rest/student/teachers",
                                "/core/rest/student/rooms",
                                "/core/rest/student/search/exams"
                        )
                );

                $this->response->setJsonContent($content);
                $this->response->send();
        }

        public function indexAction()
        {
                $request = $this->getRestRequest();
                $this->handle($request);
        }

        public function searchAction()
        {
                $request = $this->getRestRequest();
                $request->action = ObjectAccess::READ;
                $this->handle($request);
        }

        /**
         * Get REST request object.
         * @return RestRequest
         */
        private function getRestRequest()
        {
                // 
                // Read POST/PUT payload:
                // 
                if ($this->request->isPost() || $this->request->isPut()) {
                        $input = $this->getInput();
                } else {
                        $input = array(array(), array());
                }

                // 
                // Return the REST request:
                // 
                return new RestRequest(
                    $this->request->getMethod(), $this->dispatcher->getParams(), $input
                );
        }

        /**
         * Handle the REST request.
         * @param RestRequest $request The REST request object.
         * @throws SecurityException
         */
        private function handle($request)
        {
                $models = array();

                try {
                        // 
                        // Missing target collection for search:
                        // 
                        if ($request->target == "search") {
                                throw new SecurityException("invoke");
                        }

                        // 
                        // Check that call is allowed:
                        // 
                        if ($this->capabilities->hasPermission($request->role, $request->model, $request->action) == false) {
                                throw new SecurityException("access");
                        }

                        $handler = new CoreHandler($request->role);

                        // 
                        // Handle single or multiple models:
                        // 
                        if (is_numeric(key($request->data))) {
                                foreach ($request->data as $d) {
                                        $models[] = $handler->build($request->model, (array) $d);
                                }
                        } else {
                                $models[] = $handler->build($request->model, $request->data);
                        }

                        // 
                        // Pass request down to core handler:
                        // 
                        $result = $handler->action($models, $request->action, $request->params);

                        // 
                        // Send JSON encoded response:
                        // 
                        $this->response->setJsonContent($result);
                        $this->response->send();
                } catch (Exception $exception) {
                        // 
                        // Log request data:
                        // 
                        $this->report($exception, $request);

                        // 
                        // Send HTTP error status to peer:
                        // 
                        if ($exception instanceof SecurityException) {
                                switch ($exception->getMessage()) {
                                        case 'auth':
                                                $this->response->setStatusCode(401, 'Unauthorized');
                                                $this->response->send();
                                                break;
                                        case 'access':
                                                $this->response->setStatusCode(405, 'Method Not Allowed');
                                                $this->response->send();
                                                break;
                                        case 'role':
                                                $this->response->setStatusCode(403, 'Forbidden');
                                                $this->response->send();
                                                break;
                                        default :
                                                $this->response->setStatusCode(412, 'Precondition Failed');
                                                $this->response->send();
                                                break;
                                }
                        } else {
                                $this->response->setStatusCode(500, 'Internal Server Error');
                                $this->response->send();
                        }
                }
        }

        /**
         * Report exception.
         * @param Exception $exception The exception to report.
         * @param RestRequest $request The REST request object.
         */
        private function report($exception, $request)
        {
                $this->logger->system->begin();
                $this->logger->system->error(
                    print_r(array(
                        'Exception' => get_class($exception),
                        'Message'   => $exception->getMessage(),
                        'Request'   => print_r($request, true)
                        ), true
                    )
                );
                $this->logger->system->commit();
        }

}
