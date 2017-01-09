<?php

// 
// The source code is copyrighted, with equal shared rights, between the
// authors (see the file AUTHORS) and the OpenExam project, Uppsala University 
// unless otherwise explicit stated elsewhere.
// 
// File:    CoreController.php
// Created: 2014-08-20 11:36:22
// 
// Author:  Anders Lövgren (QNET/BMC CompDept)
// 

namespace OpenExam\Controllers\Service\Ajax;

use OpenExam\Controllers\Service\AjaxController;
use OpenExam\Library\WebService\Common\ServiceHandler;
use OpenExam\Library\WebService\Common\ServiceResponse;
use OpenExam\Library\WebService\Handler\CoreHandler;

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
 * Pass count = true in the params to find out the number of matching records.
 * Notice that the count is computed on the actual resultset.
 * 
 * <code>
 * // Simple count on number of matching records:
 * input: {"data":{...}:"params":{...,"count":true}}      // {"success":2}
 * 
 * // Inline the count in result:
 * input: {"data":{...}:"params":{...,"count":"inline"}}  // {"success":{"count":2,"result":[...]}}
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
class CoreController extends AjaxController
{

        /**
         * @var CoreHandler 
         */
        protected $_handler;

        public function initialize()
        {
                parent::initialize();
                $this->_handler = new CoreHandler($this->getRequest(), $this->user, $this->capabilities);
        }

        /**
         * Destructor.
         */
        public function __destruct()
        {
                unset($this->_handler);
                parent::__destruct();
        }

        /**
         * Perform create operation.
         * @param string $role The requested role.
         * @param string $type The requested model.
         */
        public function createAction($role, $type)
        {
                $response = $this->_handler->create($role, $type);
                $this->sendResponse($response);
                unset($response);
        }

        /**
         * Perform read operation.
         * @param string $role The requested role.
         * @param string $type The requested model.
         */
        public function readAction($role, $type)
        {
                $response = $this->_handler->read($role, $type);
                $this->sendResponse($response);
                unset($response);
        }

        /**
         * Perform update operation.
         * @param string $role The requested role.
         * @param string $type The requested model.
         */
        public function updateAction($role, $type)
        {
                $response = $this->_handler->update($role, $type);
                $this->sendResponse($response);
                unset($response);
        }

        /**
         * Perform delete operation.
         * @param string $role The requested role.
         * @param string $type The requested model.
         */
        public function deleteAction($role, $type)
        {
                $response = $this->_handler->delete($role, $type);
                $this->sendResponse($response);
                unset($response);
        }

        /**
         * Handle static capability checks.
         */
        public function capabilityAction()
        {
                $response = $this->_handler->capability();
                $this->sendResponse($response);
                unset($response);
        }

        /**
         * Display documentation of the AJAX service API.
         */
        public function apiAction()
        {
                // TODO: use view for displaying API docs

                $content = array(
                        "usage"   => array(
                                "/ajax/core/{role}/{model}/{action}" => "POST"
                        ),
                        "example" => array(
                                "/ajax/core/teacher/exam/create"         => "Create exam",
                                "/ajax/core/student/exam/read"           => "Read exam",
                                "/ajax/core/creator/exam/update"         => "Update exam",
                                "/ajax/core/creator/exam/delete"         => "Delete exam",
                                "/ajax/core/student/question/capability" => "Get static capabilities"
                        )
                );

                $response = new ServiceResponse($this->_handler, ServiceHandler::SUCCESS, $content);
                $this->sendResponse($response);

                unset($content);
                unset($response);
        }

}
