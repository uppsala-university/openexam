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
                        if (count($_POST) > 0) {
                                $data = $_POST;
                        } else {
                                $data = file_get_contents("php://input");
                        }

                        // 
                        // Convert data if needed/requested:
                        // 
                        if (is_string($data)) {
                                if ($this->request->getBestAccept() == 'application/json') {
                                        $data = json_decode($data);
                                } else {
                                        $data = json_decode($data);
                                }
                                if (!isset($data)) {
                                        throw new Exception("Unhandled content type");
                                }
                        }

                        if (!isset($data)) {
                                throw new Exception("Input data is missing");
                        }

                        // 
                        // Currently, we are only handling array data;
                        // 
                        if (!is_array($data)) {
                                $data = (array) $data;
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
                        $result[self::SUCCESS] = $handler->action($models, $action);
                } catch (\Exception $exception) {
                        $result[self::FAILURE] = $exception->getMessage();
                }

                $this->response->setJsonContent($result);
                $this->response->send();
        }

}
