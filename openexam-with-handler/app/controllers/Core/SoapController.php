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

class SoapController extends \OpenExam\Controllers\ServiceController
{

        public function indexAction()
        {
                if ($this->request->isGet()) {
                        if ($this->request->get("wsdl") !== null ||
                            $this->request->get("service") !== null) {
                                return $this->dispatcher->forward(
                                        array(
                                                'controller' => 'wsdl',
                                                'action'     => 'service'
                                        )
                                );
                        }
                        if ($this->request->get("types") !== null ||
                            $this->request->get("xsd") !== null) {
                                return $this->dispatcher->forward(
                                        array(
                                                'controller' => 'wsdl',
                                                'action'     => 'types'
                                        )
                                );
                        }
                }
                if ($this->request->isSoapRequested()) {
                        $config = $this->di->get('config');
                        $router = $this->di->get('router')->getRouteByName('core-soap');
                        
                        $wsdl = sprintf("%s://%s/%s", $this->request->getScheme(), $this->request->getServerAddress(), $router->getPattern());

                        $handler = new \OpenExam\Library\Core\CoreSoapService();
                        $server = new \SoapServer($wsdl);
                }
        }

}
