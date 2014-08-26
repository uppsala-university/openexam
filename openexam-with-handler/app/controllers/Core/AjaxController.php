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

/**
 * AJAX controller for core service.
 *
 * @author Anders Lövgren (QNET/BMC CompDept)
 */
class AjaxController extends \OpenExam\Controllers\ServiceController
{

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
                $hobj = self::createHandler($role, $model);
                $mobj = self::createModel($model, $_POST);

                $this->response->setJsonContent($hobj->$action($mobj));
                $this->response->send();
        }

}
