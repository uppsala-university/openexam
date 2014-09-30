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

        public function initialize()
        {
                parent::initialize();
        }

        public function apiAction()
        {
                $content = array(
                        "usage"   => array("/core/ajax/{role}/{model}/{action}" => "POST"),
                        "example" => array("/core/ajax/student/exam/read")
                );

                $this->response->setJsonContent($content);
                $this->response->send();
        }

        public function indexAction($role, $model, $action)
        {
                $result = array();
                
                try {
                        $handler = new CoreHandler($role);
                        $model = $handler->build($model, $_POST);
                        $result[self::SUCCESS] = $handler->action($model, $action);
                } catch (\Exception $exception) {
                        $result[self::FAILURE] = $exception->getMessage();
                }

                $this->response->setJsonContent($result);
                $this->response->send();
        }

}
