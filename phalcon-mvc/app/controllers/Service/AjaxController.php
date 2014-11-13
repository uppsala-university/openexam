<?php

// 
// The source code is copyrighted, with equal shared rights, between the
// authors (see the file AUTHORS) and the OpenExam project, Uppsala University 
// unless otherwise explicit stated elsewhere.
// 
// File:    AjaxController.php
// Created: 2014-11-13 09:35:22
// 
// Author:  Anders Lövgren (Computing Department at BMC, Uppsala University)
// 

namespace OpenExam\Controllers\Service;

use OpenExam\Controllers\ServiceController;

/**
 * Common base class for AJAX controllers.
 * @author Anders Lövgren (Computing Department at BMC, Uppsala University)
 */
abstract class AjaxController extends ServiceController
{

        /**
         * Send result to peer.
         * @param string $status The status label.
         * @param mixed $result The operation result.
         */
        protected function sendResponse($status, $result)
        {
                $action = $this->dispatcher->getActionName();
                $target = $this->dispatcher->getControllerName();
                
                $this->response->setJsonContent(array(
                        $status => array(
                                'target' => $target,
                                'action' => $action,
                                'return' => $result
                )));
                $this->response->send();
        }

}
