<?php

// 
// The source code is copyrighted, with equal shared rights, between the
// authors (see the file AUTHORS) and the OpenExam project, Uppsala University 
// unless otherwise explicit stated elsewhere.
// 
// File:    ServiceController.php
// Created: 2014-08-25 00:15:47
// 
// Author:  Anders Lövgren (QNET/BMC CompDept)
// 

namespace OpenExam\Controllers;

/**
 * Base class for data service controllers.
 * 
 * The ServiceController class is the base for service controllers
 * providing SOAP, REST or AJAX response as opposite to producing
 * HTML output.
 *
 * @author Anders Lövgren (QNET/BMC CompDept)
 */
class ServiceController extends \Phalcon\Mvc\Controller
{

        public function initialize()
        {
                $this->view->disable();
        }

        /**
         * Create and return model handler object for model.
         * @param string $role
         * @param string $model
         * @return \OpenExam\Library\Core\Handler\HandlerBase
         */
        protected static function createHandler($role, $model)
        {
                $name = sprintf("\OpenExam\Library\Core\Handler\%sHandler", ucfirst($model));
                $hobj = new $name($role);
                return $hobj;
        }

        /**
         * Create and return model object. The model data is assigned from
         * the data parameter.
         * @param string $model
         * @param array $data
         * @return \OpenExam\Models\ModelBase
         */
        protected static function createModel($model, $data)
        {
                $name = sprintf("\OpenExam\Models\%s", ucfirst($model));
                $mobj = new $name();
                $mobj->assign($data);
                return $mobj;
        }
        
}
