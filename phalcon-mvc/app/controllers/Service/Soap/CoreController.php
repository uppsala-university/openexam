<?php

// 
// The source code is copyrighted, with equal shared rights, between the
// authors (see the file AUTHORS) and the OpenExam project, Uppsala University 
// unless otherwise explicit stated elsewhere.
// 
// File:    CoreController.php
// Created: 2014-08-20 11:36:22
// 
// Author:  Anders Lövgren (QNET/BMC CompDept)
// 

namespace OpenExam\Controllers\Service\Soap;

use OpenExam\Controllers\ServiceController;
use OpenExam\Library\WebService\Soap\Service\AdminService;
use OpenExam\Library\WebService\Soap\Service\ContributorService;
use OpenExam\Library\WebService\Soap\Service\CoreService;
use OpenExam\Library\WebService\Soap\Service\CorrectorService;
use OpenExam\Library\WebService\Soap\Service\CreatorService;
use OpenExam\Library\WebService\Soap\Service\DecoderService;
use OpenExam\Library\WebService\Soap\Service\InvigilatorService;
use OpenExam\Library\WebService\Soap\Service\StudentService;
use OpenExam\Library\WebService\Soap\Service\TeacherService;
use OpenExam\Library\WebService\Soap\SoapRequest;
use OpenExam\Library\WebService\Soap\SoapService;
use OpenExam\Library\WebService\Soap\Wrapper\DocumentLiteral as DocumentLiteralWrapper;

/**
 * SOAP controller for the core service.
 * 
 * @author Anders Lövgren (QNET/BMC CompDept)
 */
class CoreController extends ServiceController
{

        /**
         * The SOAP service handler.
         * @var SoapService 
         */
        private $service;

        public function initialize()
        {
                parent::initialize();
                $this->createService($this->dispatcher->getActionName());
        }

        /**
         * Create (set) the SOAP service object.
         * @param string $action Create service for core action.
         */
        private function createService($action)
        {
                $request = new SoapRequest($this->request, $action, $this->url->get($this->request->getQuery('_url')));
                $this->service = $request->createService();
        }

        /**
         * Send API documentation to peer (/core/soap/api).
         */
        public function apiAction()
        {
                $this->service->sendDocumentation();
        }

        /**
         * Send WSDL documentation to peer (/core/soap/wsdl).
         */
        public function wsdlAction()
        {
                $this->response->setContentType('application/wsdl+xml', 'utf-8');
                $this->service->sendDescription();
        }

        /**
         * Admin service request (/soap/core/admin).
         */
        public function adminAction()
        {
                $this->handlerRequest(AdminService);
        }

        /**
         * Core service request (/soap/core).
         */
        public function indexAction($service = null)
        {
                if (isset($service)) {
                        $this->createService($service);
                        $this->handlerRequest(sprintf("OpenExam\Library\WebService\Soap\Service\%sService", ucfirst($service)));
                } else {
                        $this->createService("core");
                        $this->handlerRequest(CoreService);
                }
        }

        /**
         * Contributor service request (/soap/core/contributor).
         */
        public function contributorAction()
        {
                $this->handlerRequest(ContributorService);
        }

        /**
         * Core service request (/soap/core).
         */
        public function coreAction()
        {
                $this->handlerRequest(CoreService);
        }

        /**
         * Corrector service request (/soap/core/corrector).
         */
        public function correctorAction()
        {
                $this->handlerRequest(CorrectorService);
        }

        /**
         * Creator service request (/soap/core/creator).
         */
        public function creatorAction()
        {
                $this->handlerRequest(CreatorService);
        }

        /**
         * Decoder service request (/soap/core/decoder).
         */
        public function decoderAction()
        {
                $this->handlerRequest(DecoderService);
        }

        /**
         * Invigilator service request (/soap/core/invigilator).
         */
        public function invigilatorAction()
        {
                $this->handlerRequest(InvigilatorService);
        }

        /**
         * Student service request (/soap/core/student).
         */
        public function studentAction()
        {
                $this->handlerRequest(StudentService);
        }

        /**
         * Teacher service request (/soap/core/teacher).
         */
        public function teacherAction()
        {
                $this->handlerRequest(TeacherService);
        }

        private function handlerRequest($handler)
        {
                if ($this->request->has("wsdl")) {
                        $this->wsdlAction();
                        return;
                }
                if ($this->request->has("api")) {
                        $this->apiAction();
                        return;
                }
                if ($this->request->isSoapRequested()) {
                        $service = new CoreService($this->user);
                        $this->service->setHandler(new DocumentLiteralWrapper($service));
                        $this->service->setSchemaDirectory($this->config->application->schemasDir . 'soap');
                        $this->service->handleRequest();
                        return;
                }
        }

}
