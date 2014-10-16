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

use OpenExam\Controllers\ServiceController;
use OpenExam\Library\WebService\Soap\CoreService;
use OpenExam\Library\WebService\Soap\Wrapper\DocumentLiteral as DocumentLiteralWrapper;
use OpenExam\Library\WebService\Soap\SoapService;

/**
 * SOAP controller for the core service.
 * 
 * @author Anders Lövgren (QNET/BMC CompDept)
 */
class SoapController extends ServiceController
{

        /**
         * The SOAP service handler.
         * @var SoapService 
         */
        private $service;

        public function initialize()
        {
                parent::initialize();

                $location = sprintf(
                    "%s://%s%s", $this->request->getScheme(), $this->request->getServerName(), $this->url->get($this->request->getQuery('_url'))
                );

                $this->service = new SoapService('OpenExam\Library\WebService\Soap\CoreService');
                $this->service->setLocation($location);
                $this->service->setSchemaDirectory($this->config->application->schemasDir . 'soap');
                $this->service->setNamespace("http://bmc.uu.se/soap/openexam/core");
        }

        /**
         * Send API documentation to peer (/core/soap/api).
         */
        public function apiAction()
        {
                // TODO: use view for displaying API docs (DOM document)
//                $description = $this->service->getServiceDescription();
//                $domdocument = $description->getGenerator()->getDocument();

                $this->service->sendDocumentation();
        }

        /**
         * Send WSDL documentation to peer (/core/soap/wsdl).
         */
        public function wsdlAction()
        {
                $this->response->setContentType('application/wsdl+xml');
                $this->service->sendDescription();
        }

        /**
         * The main action (/core/soap/[?wsdl]).
         */
        public function indexAction()
        {
                if ($this->request->has("wsdl")) {
                        $this->service->sendDescription();
                        return;
                }
                if ($this->request->has("api")) {
                        $this->service->sendDocumentation();
                        return;
                }
                if ($this->request->isSoapRequested()) {
                        $service = new CoreService($this->user);
                        $this->service->setHandler(new DocumentLiteralWrapper($service));
                        $this->service->handleRequest();
                        return;
                }
        }

}
