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

use OpenExam\Plugins\Security\Dispatcher\DispatchHandler;
use Phalcon\Events\Event;
use Phalcon\Mvc\Dispatcher;
use Phalcon\Mvc\User\Plugin;

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
                                // Get target information:
                                // 
                                $target = self::getTarget($dispatcher);

                                // 
                                // Handle dispatch:
                                // 
                                $this->_dispatcher = new DispatchHandler($this, $dispatcher, $target['service']);
                                return $this->_dispatcher->process();
                        }
                } catch (\Exception $exception) {
                        $event->stop();
                        $this->beforeException(null, $dispatcher, $exception);
                        return false;
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
                // Get target information:
                // 
                $target = self::getTarget($dispatcher);

                // 
                // Stop event propagation:
                // 
                if (isset($event) && $event->isStopped() == false) {
                        $event->stop();
                }

                // 
                // Forward to error reporting page:
                // 
                if ($target['type'] == 'gui') {
                        $this->dispatcher->forward(
                            array(
                                    "controller" => "gui",
                                    "action"     => "exception",
                                    "params"     => array($exception),
                                    "namespace"  => "OpenExam\Controllers"
                        ));
                        return false;
                } else {
                        $this->dispatcher->forward(
                            array(
                                    "controller" => $this->_dispatcher->service,
                                    "action"     => "exception",
                                    "params"     => array($exception),
                                    "namespace"  => "OpenExam\Controllers\Service"
                        ));
                        return false;
                }
        }

        /**
         * Report dispatch issues and/or state.
         * @param string $message The reason.
         * @param array|object $data The associated data, e.g. the session data.
         */
        public function report($message = null, $data = null)
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
                                'Data'    => $data,
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
                            print_r(array(
                                'Dispatcher' => $this->_dispatcher->getData()
                                ), true
                            )
                        );
                        $this->logger->auth->commit();
                }
        }

        /**
         * Get dispatch target information.
         * 
         * <code>
         * array(
         *      'service' => web|rest|soap|ajax,
         *      'type'    => service|gui
         * )
         * </code>
         * 
         * @param Dispatcher $dispatcher The dispatcher object.
         */
        private static function getTarget($dispatcher)
        {
                if (class_exists($dispatcher->getControllerClass())) {
                        if (($parents = class_parents(
                            $dispatcher->getControllerClass()
                            ))) {
                                switch (current($parents)) {
                                        case 'OpenExam\Controllers\GuiController':
                                                return array(
                                                        'service' => 'web',
                                                        'type'    => 'gui'
                                                );
                                        case 'OpenExam\Controllers\Service\AjaxController':
                                                return array(
                                                        'service' => 'ajax',
                                                        'type'    => 'service'
                                                );
                                        case 'OpenExam\Controllers\Service\RestController':
                                                return array(
                                                        'service' => 'rest',
                                                        'type'    => 'service'
                                                );
                                        case 'OpenExam\Controllers\Service\SoapController':
                                                return array(
                                                        'service' => 'soap',
                                                        'type'    => 'service'
                                                );
                                }
                        }
                }

                $target = explode("\\", strtolower($dispatcher->getControllerClass()));
                
                print_r($target);

                if ($target[2] == "gui") {
                        return array(
                                'service' => 'web',
                                'type'    => 'gui'
                        );
                } else {
                        return array(
                                'service' => $target[3],
                                'type'    => $target[2]
                        );
                }
        }

}
