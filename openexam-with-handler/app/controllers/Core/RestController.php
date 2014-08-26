<?php

// 
// The source code is copyrighted, with equal shared rights, between the
// authors (see the file AUTHORS) and the OpenExam project, Uppsala University 
// unless otherwise explicit stated elsewhere.
// 
// File:    RestController.php
// Created: 2014-08-20 11:35:41
// 
// Author:  Anders Lövgren (QNET/BMC CompDept)
// 

namespace OpenExam\Controllers\Core;

use \OpenExam\Library\Core\Handler\HandlerBase;

/**
 * REST request helper class.
 */
class RestRequest
{

        public $target;
        public $method;
        public $action;
        public $model;
        public $role;
        public $data = array();

        public function __construct($method, $params)
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
                        $this->setData();
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

        private function setData()
        {
                switch ($this->method) {
                        case 'POST':
                                $this->data = $_POST;
                                break;
                        case 'PUT':
                                parse_str(file_get_contents("php://input"), $this->data);
                                break;
                }
        }

}

/**
 * REST controller for core service.
 *
 * @author Anders Lövgren (QNET/BMC CompDept)
 */
class RestController extends \OpenExam\Controllers\ServiceController
{

        public function initialize()
        {
                parent::initialize();
        }

        public function apiAction()
        {
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
                $request->action = HandlerBase::read;
                $this->handle($request);
        }

        private function getRestRequest()
        {
                return new RestRequest(
                    $this->request->getMethod(), $this->dispatcher->getParams()
                );
        }

        private function handle($request)
        {
                $action = $request->action;

                $hobj = self::createHandler($request->role, $request->model);
                $mobj = self::createModel($request->model, $request->data);

                $this->response->setJsonContent($hobj->$action($mobj));
                $this->response->send();
        }

}
