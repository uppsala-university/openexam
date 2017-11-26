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
//          Anders Lövgren (BMC-IT)
// 

namespace OpenExam\Controllers;

use Exception;
use OpenExam\Library\Core\Error;
use OpenExam\Library\Security\Exception as SecurityException;
use OpenExam\Library\Security\Roles;
use Phalcon\Config;
use Phalcon\Logger;
use Phalcon\Mvc\Dispatcher;
use Phalcon\Mvc\Dispatcher\Exception as DispatcherException;
use Phalcon\Mvc\View;
use const CONFIG_DIR;

/**
 * Base class for GUI controllers.
 * 
 * Controllers that (mostly) have actions with visual interface can derive from
 * this class to use the main layout.
 *  
 * @author Ahsan Shahzad (MedfarmDoIT)
 * @author Anders Lövgren (BMC-IT)
 */
class GuiController extends ControllerBase
{

        public function initialize()
        {
                parent::initialize();

                $this->detectAjaxRequest();
                $this->detectPrimaryRole();
                $this->detectRequestParams();

                // 
                // Set authenticators list for HTML template:
                // 
                $this->view->setVar("authenticators", $this->auth->getChain("web"));

                // 
                // Set user interface theme:
                // 
                if($this->cookies->has("theme")) {
                        $theme = $this->cookies->get("theme")->getValue();
                        $this->view->setVar("theme", $theme);
                }

                // 
                // Try to acquire all roles:
                // 
                $this->user->acquire(array(
                        Roles::ADMIN,
                        Roles::CREATOR,
                        Roles::CONTRIBUTOR,
                        Roles::CORRECTOR,
                        Roles::INVIGILATOR,
                        Roles::DECODER,
                        Roles::STUDENT,
                        Roles::TEACHER
                ));
        }

        /**
         * Detect AJAX request.
         */
        private function detectAjaxRequest()
        {
                if ($this->request->isAjax()) {
                        $this->view->setRenderLevel(View::LEVEL_ACTION_VIEW);
                } else {
                        $this->view->setLayout('main');
                }
        }

        /**
         * Detect primary role.
         */
        private function detectPrimaryRole()
        {
                if ($this->request->hasPost('role')) {
                        $this->injectPrimaryRole($this->request->getPost('role', 'string'));
                }
                if ($this->request->hasQuery('role')) {
                        $this->injectPrimaryRole($this->request->getQuery('role', 'string'));
                }
        }

        /**
         * Set primary role if unset.
         * @param string $role The primary role.
         */
        protected function injectPrimaryRole($role)
        {
                if ($this->user->hasPrimaryRole() == false) {
                        $this->user->setPrimaryRole($role);
                        $this->dispatcher->setParam('role', $role);
                }
        }

        /**
         * Detect requested exam or question ID.
         */
        private function detectRequestParams()
        {
                if (($id = $this->request->getPost('question_id', 'int')) ||
                    ($id = $this->request->getQuery('question_id', 'int')) ||
                    ($id = $this->dispatcher->getParam('question_id', 'int'))) {
                        $this->dispatcher->setParam('qid', $id);
                }

                if (($id = $this->request->getPost('exam_id', 'int')) ||
                    ($id = $this->request->getQuery('exam_id', 'int')) ||
                    ($id = $this->dispatcher->getParam('exam_id', 'int'))) {
                        $this->dispatcher->setParam('eid', $id);
                }
        }

        /**
         * The exception handler.
         * @param Exception $exception
         */
        public function exceptionAction($exception)
        {
                // 
                // See https://github.com/phalcon/cphalcon/issues/2851 for discussion under
                // which circumstances forward to error page will fail.
                // 
                echo $exception->getMessage();
                $error = new Error($exception);

                if ($exception instanceof DispatcherException) {
                        switch ($exception->getCode()) {
                                case Dispatcher::EXCEPTION_ACTION_NOT_FOUND:
                                case Dispatcher::EXCEPTION_HANDLER_NOT_FOUND:
                                        $this->dispatcher->forward(array(
                                                'controller' => 'error',
                                                'action'     => 'show404',
                                                'namespace'  => 'OpenExam\Controllers\Gui',
                                                'params'     => array('error' => $error)
                                        ));
                                        return false;
                        }
                } else {

                        $this->response->setStatusCode(
                            $error->getStatus(), $error->getString()
                        );

                        switch ($error->getStatus()) {
                                case Error::INTERNAL_SERVER_ERROR:
                                        $this->dispatcher->forward(array(
                                                'controller' => 'error',
                                                'action'     => 'show500',
                                                'namespace'  => 'OpenExam\Controllers\Gui',
                                                'params'     => array('error' => $error)
                                        ));
                                        $this->report($exception);
                                        return false;
                                case Error::SERVICE_UNAVAILABLE:
                                        $this->dispatcher->forward(array(
                                                'controller' => 'error',
                                                'action'     => 'show503',
                                                'namespace'  => 'OpenExam\Controllers\Gui',
                                                'params'     => array('error' => $error)
                                        ));
                                        $this->report($exception);
                                        return false;
                                case Error::NOT_FOUND:
                                        $this->dispatcher->forward(array(
                                                'controller' => 'error',
                                                'action'     => 'show404',
                                                'namespace'  => 'OpenExam\Controllers\Gui',
                                                'params'     => array('error' => $error)
                                        ));
                                        $this->report($exception);
                                        return false;
                                default :
                                        $this->dispatcher->forward(array(
                                                'controller' => 'error',
                                                'action'     => 'showError',
                                                'namespace'  => 'OpenExam\Controllers\Gui',
                                                'params'     => array('error' => $error)
                                        ));
                                        $this->report($exception);
                                        return false;
                        }
                }
        }

        /**
         * Get access permission map.
         * 
         * @param string $controller The requested controller.
         * @param string $action The requested action.
         * @return Config
         */
        private function getPermissionMap($controller, $action)
        {
                $access = new Config(require CONFIG_DIR . '/access.def');
                $permit = $access->private->$controller->$action;

                return $permit;
        }

        /**
         * Check dispatched route.
         * 
         * @param string $controller The requested controller.
         * @param string $action The requested action.
         * @param array $params Optional parameters.
         * 
         * @return boolean
         * @throws SecurityException
         */
        private function checkRoute($controller, $action, $params)
        {
                // 
                // Get access permissions:
                // 
                $permit = $this->getPermissionMap($controller, $action);

                // 
                // Set dispatcher params if defined:
                // 
                if (isset($params)) {
                        foreach ($params as $key => $val) {
                                $this->dispatcher->setParam($key, $val);
                        }
                }

                // 
                // Inject primary role if requested:
                // 
                if (($role = $this->dispatcher->getParam('role'))) {
                        $this->injectPrimaryRole($role);
                }

                // 
                // Bypass for non-configured controller/action:
                // 
                if (!isset($permit)) {
                        if ($this->logger->access->getLogLevel() >= Logger::DEBUG) {
                                $this->logger->access->debug(sprintf("Bypass access restriction on %s -> %s (access rule missing)", $controller, $action));
                        }
                        return true;
                }
                if (count($permit->toArray()) == 0) {
                        if ($this->logger->access->getLogLevel() >= Logger::DEBUG) {
                                $this->logger->access->debug(sprintf("Bypass access restriction on %s -> %s (access rules empty)", $controller, $action));
                        }
                        return true;
                }

                // 
                // Using roles == '*' means access for all roles:
                // 
                if ($permit[0] == '*') {
                        if ($this->logger->access->getLogLevel() >= Logger::DEBUG) {
                                $this->logger->access->debug(sprintf("Bypass access restriction on %s -> %s (access rule is '*')", $controller, $action));
                        }
                        return true;
                }

                // 
                // Check exam access:
                // 
                if (($id = $this->dispatcher->getParam('eid'))) {
                        if (($roles = $this->user->acquire($permit, $id))) {
                                if ($this->logger->access->getLogLevel() >= Logger::DEBUG) {
                                        $this->logger->access->debug(sprintf("Permitted exam level access on %s -> %s (id: %d, roles: %s)", $controller, $action, $id, implode(",", $roles)));
                                }
                                $this->injectPrimaryRole($roles[0]);
                                return true;
                        } else {
                                throw new SecurityException(sprintf(
                                    "You are not allowed to access this exam. Please contact <a href='mailto:%s'>%s</a> if you think this is an error.", $this->config->contact->addr, $this->config->contact->name
                                ), Error::METHOD_NOT_ALLOWED
                                );
                        }
                }

                // 
                // Check question access:
                // 
                if (($id = $this->dispatcher->getParam('qid'))) {
                        if (($roles = $this->user->acquire(array(Roles::CORRECTOR), $id))) {
                                if ($this->logger->access->getLogLevel() >= Logger::DEBUG) {
                                        $this->logger->access->debug(sprintf("Permitted question level access on %s -> %s (id: %d, roles: %s)", $controller, $action, $id, implode(",", $roles)));
                                }
                                $this->injectPrimaryRole($roles[0]);
                                return true;
                        } else {
                                throw new SecurityException(sprintf(
                                    "You are not allowed to access this question. Please contact <a href='mailto:%s'>%s</a> if you think this is an error.", $this->config->contact->addr, $this->config->contact->name
                                ), Error::METHOD_NOT_ALLOWED
                                );
                        }
                }

                // 
                // Check role access:
                // 
                if (($roles = $this->user->acquire($permit))) {
                        if ($this->logger->access->getLogLevel() >= Logger::DEBUG) {
                                $this->logger->access->debug(sprintf("Permitted role based access on %s -> %s (roles: %s)", $controller, $action, implode(",", $roles)));
                        }
                        $this->injectPrimaryRole($roles[0]);
                        return true;
                }

                // 
                // Nuke access with a proper contact us message:
                // 
                throw new SecurityException(sprintf(
                    "You are not allowed to access this URL. Please contact <a href='mailto:%s'>%s</a> if you think this is an error.", $this->config->contact->addr, $this->config->contact->name
                ), Error::METHOD_NOT_ALLOWED
                );
        }

        /**
         * Perform access check.
         * 
         * Check if caller has permission to access route (controller -> action)
         * based on rules in user configuration file access.def. The control
         * is done against list of explicit defined private URL's. If a route
         * is not defined, then access is permitted.
         * 
         * Object specific check (thru role acquire) is done if exam or question 
         * ID is passed in request. The order of access check is: question,
         * exam and then generic role acquire check. 
         * 
         * For actions where exam/question ID is passed as method argument, 
         * set dispatch parameters or pass them as method argument. Notice that
         * setParams() will replace other dispatch parameters.
         * 
         * <code>
         * $this->dispatcher->setParam('eid', 18372);
         * $this->dispatcher->setParam('qid', 69456);
         * 
         * $this->dispatcher->setParams(array(
         *      'eid' => 18372
         *      'qid' => 29456
         * ));
         * 
         * $this->checkAccess(array('eid' => 18372));
         * $this->checkAccess(array('qid' => 69456));
         * </code>
         * 
         * The same method can also be used for setting primary role:
         * <code>
         * $this->dispatcher->setParam('role', 'contributor');
         * $this->checkAccess();
         * 
         * $this->checkAccess(array('role' => 'contributor'));
         * </code>
         * 
         * Return true if access is permitted. Throws an exception if the route 
         * is private and no permitted role was acquired.
         * 
         * @param array $params The object ID's
         * @return boolean
         * @throws SecurityException
         */
        protected function checkAccess($params = null)
        {
                $controller = $this->dispatcher->getControllerName();
                $action = $this->dispatcher->getActionName();

                if ($this->checkRoute($controller, $action, $params)) {
                        if ($this->logger->access->getLogLevel() >= Logger::INFO) {
                                $this->logger->access->info(sprintf("Using primary role %s for %s -> %s (eid: %d, qid: %d)", $this->user->getPrimaryRole(), $controller, $action, $this->dispatcher->getParam('eid'), $this->dispatcher->getParam('qid')));
                        }
                }
                return true;
        }

}
