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

use OpenExam\Library\Core\Error;
use OpenExam\Library\Security\Exception as SecurityException;
use OpenExam\Library\Security\Roles;
use Phalcon\Config;
use Phalcon\Logger;
use Phalcon\Mvc\Dispatcher;
use Phalcon\Mvc\Dispatcher\Exception as DispatcherException;
use Phalcon\Mvc\View;

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

                // 
                // Normal exception handling is not working at dispatch time.
                // We need to use try/catch with forward to error page.
                // 
                
                try {
                        if ($this->request->isAjax()) {
                                $this->view->setRenderLevel(View::LEVEL_ACTION_VIEW);
                        } else {
                                $this->view->setLayout('main');
                        }
                        
                        $this->checkAccess(
                            $this->dispatcher->getControllerName(), $this->dispatcher->getActionName()
                        );
                        
                        $this->view->setVar("authenticators", $this->auth->getChain("web"));
                } catch (\Exception $exception) {
                        return $this->exceptionAction($exception);
                }
        }

        /**
         * The exception handler.
         * @param \Exception $exception
         */
        public function exceptionAction($exception)
        {
                if ($exception instanceof DispatcherException) {
                        switch ($exception->getCode()) {
                                case Dispatcher::EXCEPTION_ACTION_NOT_FOUND:
                                case Dispatcher::EXCEPTION_HANDLER_NOT_FOUND:
                                        $this->dispatcher->forward(array(
                                                'controller' => 'error',
                                                'action'     => 'show404',
                                                'namespace'  => 'OpenExam\Controllers\Gui',
                                                'params'     => array('exception' => $exception)
                                        ));
                                        return false;
                        }
                }

                if ($exception->getCode() == 0) {
                        $this->dispatcher->forward(array(
                                'controller' => 'error',
                                'action'     => 'show503',
                                'namespace'  => 'OpenExam\Controllers\Gui',
                                'params'     => array('exception' => $exception)
                        ));
                        $this->report($exception);
                        return false;
                } else {
                        $this->report($exception);
                        $this->dispatcher->forward(array(
                                'controller' => 'error',
                                'action'     => 'showError',
                                'namespace'  => 'OpenExam\Controllers\Gui',
                                'params'     => array('exception' => $exception)
                        ));
                        return false;
                }
        }

        /**
         * Perform access check.
         * 
         * Check if caller has permission to access URL (controller -> action)
         * based on rules in user configuration file access.def. The control
         * is done against list of explicit defined private URL's. If a URL
         * is not defined, then access is permitted.
         * 
         * Object specific check (thru role aquire) is done if exam or question 
         * ID is passed in request. The order of access check is: question,
         * exam and then generic role aquire check.
         * 
         * Return true if access is permitted. Throws exception if URL is 
         * private and none accepted role was aquired.
         * 
         * @param string $controller The target controller.
         * @param string $action The target action.
         * @return boolean
         * @throws SecurityException
         */
        private function checkAccess($controller, $action)
        {
                $access = new Config(require CONFIG_DIR . '/access.def');
                $permit = $access->private->$controller->$action;

                // 
                // Bypass for non-configured controller/action:
                // 
                if (!isset($permit)) {
                        if ($this->logger->auth->getLogLevel() >= Logger::DEBUG) {
                                $this->logger->auth->debug(sprintf("Bypass access restriction for %s on %s -> %s (access rule missing)", $this->user->getPrincipalName(), $controller, $action));
                        }
                        return true;
                }
                if (count($permit->toArray()) == 0) {
                        if ($this->logger->auth->getLogLevel() >= Logger::DEBUG) {
                                $this->logger->auth->debug(sprintf("Bypass access restriction for %s on %s -> %s (access rules empty)", $this->user->getPrincipalName(), $controller, $action));
                        }
                        return true;
                }

                // 
                // Using roles == '*' means access for all roles:
                // 
                if ($permit[0] == '*') {
                        if ($this->logger->auth->getLogLevel() >= Logger::DEBUG) {
                                $this->logger->auth->debug(sprintf("Bypass access restriction for %s on %s -> %s (access rule is '*')", $this->user->getPrincipalName(), $controller, $action));
                        }
                        return true;
                }

                // 
                // Check question access:
                // 
                if (($id = $this->request->get('q_id', 'int')) ||
                    ($id = $this->request->get('questionId', 'int')) ||
                    ($id = $this->dispatcher->getParam('questId', 'int'))) {
                        if ($this->user->roles->aquire(Roles::CORRECTOR, $id)) {
                                if ($this->logger->auth->getLogLevel() >= Logger::DEBUG) {
                                        $this->logger->auth->debug(sprintf("Permitted question access for %s on %s -> %s (id: %d, roles: %s)", $this->user->getPrincipalName(), $controller, $action, $id, Roles::CORRECTOR));
                                }
                                return true;
                        }
                }

                // 
                // Check exam access:
                // 
                if (($id = $this->request->get('exam_id', 'int')) ||
                    ($id = $this->request->get('examId', 'int')) ||
                    ($id = $this->dispatcher->getParam('examId', 'int'))) {
                        if (($roles = $this->user->aquire($permit, $id))) {
                                if ($this->logger->auth->getLogLevel() >= Logger::DEBUG) {
                                        $this->logger->auth->debug(sprintf("Permitted exam access for %s on %s -> %s (id: %d, roles: %s)", $this->user->getPrincipalName(), $controller, $action, $id, implode(",", $roles)));
                                }
                                return true;
                        }
                }

                // 
                // Check role access:
                // 
                if (($roles = $this->user->aquire($permit))) {
                        if ($this->logger->auth->getLogLevel() >= Logger::DEBUG) {
                                $this->logger->auth->debug(sprintf("Permitted role access for %s on %s -> %s (roles: %s)", $this->user->getPrincipalName(), $controller, $action, implode(",", $roles)));
                        }
                        return true;
                }

                // 
                // Nuke access with a proper contact us message:
                // 
                throw new SecurityException(sprintf(
                    "You are not allowed to access this URL. Please <a href=\"mailto:%s\">%s</a> if you think this is an error.", $this->config->contact->addr, $this->config->contact->name
                ), Error::METHOD_NOT_ALLOWED
                );
        }

}
