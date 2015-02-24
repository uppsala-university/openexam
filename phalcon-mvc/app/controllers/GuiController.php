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
class GuiController extends \Phalcon\Mvc\Controller
{

        public function initialize()
        {
                $errormask = (E_COMPILE_ERROR | E_CORE_ERROR | E_ERROR | E_RECOVERABLE_ERROR | E_USER_ERROR);
                set_exception_handler(array($this, 'exception_handler'));
                set_error_handler(array($this, 'error_handler'), $errormask);
                
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

        /**
         * Log error and throw exception.
         * @param int $code The error level (severity).
         * @param string $message The error message.
         * @param string $file The error file.
         * @param string $line The error line.
         * @throws \ErrorException
         */
        public function error_handler($code, $message, $file, $line)
        {
                // 
                // Log triggered error:
                // 
                $this->logger->system->log($code, sprintf("%s in %s on line %d", $message, $file, $line, $code));

                // 
                // Throw exception for errors above threshold:
                // 
                throw new \ErrorException($message, 0, $code, $file, $line);
        }

        /**
         * The exception handler.
         * @param \Exception $exception
         */
        public function exception_handler($exception)
        {
                $this->report($exception);
                $this->dispatcher->forward(array(
                        'controller' => 'error',
                        'action'     => 'show503',
                        'namespace'  => 'OpenExam\Controllers\Gui',
                        'params'     => array('exception' => $exception)
                ));
        }

        /**
         * Report service exception.
         * @param \Exception $exception The exception to report.
         * @param ServiceRequest $request The REST request object.
         */
        protected function report($exception)
        {
                $this->logger->system->begin();
                $this->logger->system->error(print_r(array(
                        'Exception' => array(
                                'Type'    => get_class($exception),
                                'Message' => $exception->getMessage(),
                                'File'    => $exception->getFile(),
                                'Line'    => $exception->getLine(),
                                'Code'    => $exception->getCode()
                        ),
                        'Request'   => array(
                                'Server' => sprintf("%s (%s)", $this->request->getServerName(), $this->request->getServerAddress()),
                                'Method' => $this->request->getMethod(),
                                'Query'  => print_r($this->request->get(), true)
                        ),
                        'Source'    => array(
                                'User'   => $this->user->getPrincipalName(),
                                'Role'   => $this->user->getPrimaryRole(),
                                'Remote' => $this->request->getClientAddress(true)
                        )
                        ), true));
                $this->logger->system->commit();
        }

}
