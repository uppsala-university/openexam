<?php

// 
// The source code is copyrighted, with equal shared rights, between the
// authors (see the file AUTHORS) and the OpenExam project, Uppsala University 
// unless otherwise explicit stated elsewhere.
// 
// File:    GuiController.php
// Created: 2014-08-27 11:35:20
// 
// Author:  Ahsan Shahzad (MedfarmDoIT)
// 

namespace OpenExam\Controllers;

/**
 * Base class for gui controllers.
 * 
 * The GuiController class serve as base class for all gui controllers
 * and helps to setup templates for views
 *  
 * @author Ahsan Shahzad (MedfarmDoIT)
 */
class GuiController extends ControllerBase
{

        public function initialize()
        {
                parent::initialize();

                $controller = $this->dispatcher->getControllerName();
                $action = $this->dispatcher->getActionName();

                $access = new \Phalcon\Config(require CONFIG_DIR . '/access.def');

                // if it is a private page
                if ($controller != 'result' && $action != 'download' && $access->private->$controller->$action && count($access->private->$controller->$action->toArray())) {

                        $allowedRoles = $access->private->$controller->$action->toArray();

                        // check if person is logged in, if not redirect him to login page
                        if (!$this->session->has('authenticated')) {
                                header('Location: ' . $this->url->get('#login-me'));
                        }

                        # if this page is not accessable for all logged in persons, 
                        # check if logged in person has appropriate role to access
                        # this page
                        if (!array_search('*', $allowedRoles)) {

                                if (($examId = $_REQUEST['exam_id']) || ($examId = $_REQUEST['examId']) || ($examId = $this->dispatcher->getParam("examId"))) {

                                        $aquired = $this->user->aquire($allowedRoles, $examId);

                                        if (
                                            !$aquired &&
                                            array_search('corrector', $allowedRoles) &&
                                            (($qId = $_REQUEST['q_id']) || ($qId = $_REQUEST['questionId']) || ($qId = $this->dispatcher->getParam("questId")))
                                        ) {
                                                $aquired = $this->user->aquire(array('corrector'), $qId);
                                        }
                                } else {
                                        $aquired = $this->user->aquire($allowedRoles);
                                }

                                if (!$aquired) {
                                        die("Appologies! you don't have permissions to access this URL.<br> "
                                            . "Please <a href='mailto:ahsan.shahzad@medfarm.uu.se'>contact us</a> in case you should be allowed.");
                                }
                        }
                }

                $this->view->setVar("authenticators", $this->auth->getChain("web"));
                $this->view->setLayout('main');
        }

}
