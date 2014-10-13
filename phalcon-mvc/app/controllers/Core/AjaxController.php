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
 * data: {"id":56}
 * 
 * // Same as previous:
 * data: {"data":{"id":56}}
 * 
 * // Pagination example:
 * data: {"params":{"limit":[1,0]}}
 * 
 * // Somewhat more complex:
 * data: {"params":{"columns":["id","name","status"],"conditions":[["created > :min: AND created < :max:",{"min":"2013-01-01","max":"2014-01-01"},{"min":2,"max":2}]],"group":["id","name"],"having":"name = 'Kamil'","order":["name","id"],"limit":20,"offset":20}}
 * </code>
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

        public function apiAction()
        {
                // TODO: use view for displaying API docs

                $content = array(
                        "usage"   => array("/core/ajax/{role}/{model}/{action}" => "POST"),
                        "example" => array("/core/ajax/student/exam/read")
                );

                $this->response->setJsonContent($content);
                $this->response->send();
        }

        public function indexAction($role, $type, $action)
        {
                $result = array();
                $models = array();

                try {
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
                                        $input = json_decode($input);
                                } else {
                                        $input = json_decode($input);
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
                        // Assume input is data by default:
                        // 
                        if (!isset($data) && !isset($params)) {
                                $data = $input;
                        }
                        if (!isset($data)) {
                                $data = array();
                        }
                        if (!isset($params)) {
                                $params = array();
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
                        // Perform action on model(s):
                        // 
                        $result[self::SUCCESS] = $handler->action($models, $action, $params);
                } catch (\Exception $exception) {
                        $result[self::FAILURE] = $exception->getMessage();
                }

                $this->response->setJsonContent($result);
                $this->response->send();
        }

}
