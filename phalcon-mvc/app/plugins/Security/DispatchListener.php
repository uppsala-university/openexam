<?php

// 
// The source code is copyrighted, with equal shared rights, between the
// authors (see the file AUTHORS) and the OpenExam project, Uppsala University 
// unless otherwise explicit stated elsewhere.
// 
// File:    DispatchListener.php
// Created: 2014-11-07 00:48:02
// 
// Author:  Anders Lövgren (QNET/BMC CompDept)
// 

namespace OpenExam\Plugins\Security;

use Phalcon\Events\Event;
use Phalcon\Mvc\Dispatcher;
use Phalcon\Mvc\User\Plugin;
use OpenExam\Plugins\Security\Dispatcher\DispatchHandler;

/**
 * Listen for dispatch events.
 * 
 * This class listen for dispatch event from the event manager and uses ACL
 * to enforce authentication for non-public controller/actions.
 * 
 * @author Anders Lövgren (QNET/BMC CompDept)
 */
class DispatchListener extends Plugin
{

        /**
         * The dispatch handler.
         * @var DispatchHandler 
         */
        private $_dispatcher;

        /**
         * Dispatch event listener.
         * 
         * Called before execute any action in the application. From here we 
         * handle these duties:
         * 
         * <ol>
         * <li>Restrict access to all actions/controllers.</li>
         * <li>Handle authentication (on demand or by user request).</li>
         * <li>Inject user object.</li>
         * <li>Perform additional task, like impersonation.</li>
         * </ol>
         * 
         * @param Event $event The dispatch event.
         * @param Dispatcher $dispatcher The dispatcher object.
         */
        public function beforeDispatch(Event $event, Dispatcher $dispatcher)
        {
                try {
                        if ($dispatcher->wasForwarded()) {
                                // 
                                // Bypass access control if called in a chain:
                                //                 
                                $ptarget = $dispatcher->getPreviousControllerName();
                                $paction = $dispatcher->getPreviousActionName();
                                
                                $ctarget = $dispatcher->getControllerName();
                                $caction = $dispatcher->getActionName();

                                $this->logger->auth->debug(sprintf(
                                        "Bypass acccess control in forward dispatch %s -> %s (%s -> %s)", $ctarget, $caction, $ptarget, $paction
                                ));
                                return true;
                        } else {
                                // 
                                // Handle dispatch:
                                // 
                                $this->_dispatcher = new DispatchHandler($this, $dispatcher);
                                return $this->_dispatcher->process();
                        }
                } catch (\Exception $exception) {
                        $event->stop();
                        $this->beforeException(null, $dispatcher, $exception);
                        return false;
                }
        }

        /**
         * Report dispatch issues, exceptions or state.
         * @param string $message The reason.
         * @param array|object $data The associated data, e.g. the session data.
         * @param \Exception $exception The exception to report.
         */
        public function report($message = null, $data = null, $exception = null)
        {
                // 
                // Log message:
                // 
                if (isset($message)) {
                        $this->logger->auth->begin();
                        $this->logger->auth->alert(
                            print_r(array(
                                'Message' => 'Possible breakin attempt',
                                'Reason'  => $message,
                                'Data'    => print_r($data, true),
                                'From'    => $this->request->getClientAddress(true)
                                ), true
                            )
                        );
                        $this->logger->auth->commit();
                }

                // 
                // Log this dispatcher:
                // 
                if (isset($this->_dispatcher)) {
                        $this->logger->auth->begin();
                        $this->logger->auth->debug(
                            print_r($this->_dispatcher->getData(), true)
                        );
                        $this->logger->auth->commit();
                }

                // 
                // Log exception:
                // 
                if (isset($exception)) {
                        $request = $this->request->get();
                        $request['pass'] = "*****";

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
                                        'Query'  => print_r($request, true)
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

        /**
         * Handle dispatch exceptions.
         * @param Event $event The dispatch event.
         * @param Dispatcher $dispatcher The dispatcher object.
         * @param \Exception $exception
         */
        public function beforeException($event, $dispatcher, $exception)
        {
                // 
                // Log exception:
                // 
                $this->report(null, null, $exception);

                // 
                // Stop event propagation:
                // 
                if (isset($event) && $event->isStopped() == false) {
                        $event->stop();
                }

                // 
                // Forward to error reporting page:
                // 
                if ($this->_dispatcher->service == "web" ||
                    $this->_dispatcher->service == "") {
                        switch ($exception->getCode()) {
                                case Dispatcher::EXCEPTION_ACTION_NOT_FOUND:
                                case Dispatcher::EXCEPTION_HANDLER_NOT_FOUND:
                                        $dispatcher->forward(array(
                                                'controller' => 'error',
                                                'action'     => 'show404',
                                                'namespace'  => 'OpenExam\Controllers\Gui',
                                                'params'     => array('exception' => $exception)
                                        ));
                                        break;
                                default:
                                        $dispatcher->forward(array(
                                                'controller' => 'error',
                                                'action'     => 'show503',
                                                'namespace'  => 'OpenExam\Controllers\Gui',
                                                'params'     => array('exception' => $exception)
                                        ));
                                        break;
                        }
                        return true;
                }
        }

}
