<?php

// 
// The source code is copyrighted, with equal shared rights, between the
// authors (see the file AUTHORS) and the OpenExam project, Uppsala University 
// unless otherwise explicit stated elsewhere.
// 
// File:    SoapService.php
// Created: 2014-08-21 01:05:58
// 
// Author:  Anders Lövgren (QNET/BMC CompDept)
// 

namespace OpenExam\Library\WebService\Soap;

use OpenExam\Library\WebService\Wsdl\ServiceDescription;
use SoapServer;

/**
 * The SOAP service.
 * 
 * Basic usage needs an class to handle requests, the SOAP endpoint and an
 * namespace:
 * 
 * <code>
 * $service = new SoapService("OpenExam\Library\WebService\Soap\CoreService");
 * $service->setLocation("http://localhost/openexam/core/soap");
 * $service->setNamespace("http://bmc.uu.se/soap/openexam/core");
 * </code>
 * 
 * Once the service has been stabilized, it can be switched over to schema
 * directory mode:
 * 
 * <code>
 * $this->service->setSchemaDirectory($this->config->application->schemasDir . 'soap');
 * </code>
 * 
 * The schema mode enable use of local WSDL file (for customization) and also
 * defaults to system soap.wsdl_cache_enabled (disabled in non-schema mode).
 * 
 * Call sendDescription() to send service description (WSDL) to peer. This 
 * is typical done from the index action in the controller:
 * 
 * <code>
 * public function indexAction()
 * {
 *      if ($this->request->has("wsdl")) {
 *              $this->service->sendDescription();      // Send WSDL
 *      } elseif ($this->request->isSoapRequested()) {
 *              $this->service->handleRequest();        // Handle SOAP request
 *      } else {
 *              $this->service->sendDocumentation();    // Send API doc
 *      }
 * }
 * </code>
 * 
 * @author Anders Lövgren (QNET/BMC CompDept)
 */
class SoapService
{

        /**
         * The SOAP service class.
         * @var string 
         */
        protected $class;
        /**
         * The SOAP service instance.
         * @var SoapHandler
         */
        protected $handler;
        /**
         * The service location.
         * @var string
         */
        protected $location;
        /**
         * The service namespace.
         * @var string 
         */
        protected $namespace;
        /**
         * The WSDL schema directory.
         * @var string  
         */
        protected $schemas;

        /**
         * Constructor.
         * @param string $class The SOAP service class.
         * @param string $location The service location.
         * @param string $namespace The service namespace.
         */
        public function __construct($class, $location = null, $namespace = null)
        {
                if (!extension_loaded('soap')) {
                        throw new \SoapFault("Receiver", "Server can't handle SOAP request.");
                }
                $this->class = $class;
                $this->location = $location;
                $this->namespace = $namespace;
        }

        /**
         * Set the SOAP service location.
         * @param string $location
         */
        public function setLocation($location)
        {
                $this->location = $location;
        }

        /**
         * Set the SOAP service namespace.
         * @param string $namespace
         */
        public function setNamespace($namespace)
        {
                $this->namespace = $namespace;
        }

        /**
         * Set handler for SOAP request.
         * @param SoapHandler $handler
         */
        public function setHandler($handler)
        {
                $this->handler = $handler;
        }

        /**
         * Set the WSDL schema directory.
         * @param string $schemas
         */
        public function setSchemaDirectory($schemas)
        {
                $this->schemas = $schemas;
        }

        /**
         * Send WSDL for the SOAP service.
         */
        public function sendDescription()
        {
                $description = $this->getServiceDescription();
                $description->send(ServiceDescription::FORMAT_XML);
        }

        /**
         * Send HTML documentation for the SOAP service.
         */
        public function sendDocumentation()
        {
                $description = $this->getServiceDescription();
                $description->send(ServiceDescription::FORMAT_HTML);
        }

        /**
         * Get service description object.
         * @return ServiceDescription
         */
        public function getServiceDescription()
        {
                $description = new ServiceDescription($this->class, $this->location, $this->namespace);
                $description->setNamespace($this->namespace);
                return $description;
        }

        /**
         * Get service description file.
         * 
         * Returns the path to the local customized WSDL file. This file 
         * contains a local customized version of the service description.
         * 
         * It depends on if setSchemaDirectory() has been called. Unless the
         * schema directory has been set, this function will always return null
         * to indicate that the SOAP server should always get the service 
         * description from an URL in WSDL mode.
         * 
         * @return string
         */
        private function getDescriptionFilename()
        {
                if (!isset($this->schemas)) {
                        return null;
                }
                $name = strtolower(trim(strrchr($this->class, '\\'), '\\'));
                $path = sprintf("%s/%s.wsdl", $this->schemas, $name);

                return $path;
        }

        /**
         * Handle the SOAP request.
         */
        public function handleRequest()
        {
                $description = $this->getServiceDescription();

                // 
                // Create cached service description:
                // 
                if (($filename = $this->getDescriptionFilename()) != null) {
                        if (!file_exists($filename)) {
                                $description->save($filename);
                        }
                }

                // 
                // Set URI of service description:
                // 
                if ($filename != null && file_exists($filename)) {
                        $this->description = $filename;
                } else {
                        $this->description = $this->location . '?wsdl';
                }

                // 
                // Turn off WSDL cache when not using schema directory:
                // 
                if (!isset($this->schemas)) {
                        ini_set("soap.wsdl_cache_enabled", "0");
                }

                // 
                // Use SOAP document/literal mode:
                // 
                $options = array(
                        'uri'      => $this->description,
                        'location' => $this->location,
                        'style'    => SOAP_DOCUMENT,
                        'use'      => SOAP_LITERAL,
                        'classmap' => $description->getGenerator()->getClassMap()
                );

                // 
                // Create SOAP server using WSDL mode:
                // 
                $server = new SoapServer($this->description, $options);

                // 
                // Handle request using handler object (if set) or the SOAP
                // service class.
                // 
                if (isset($this->handler)) {
                        $server->setObject($this->handler);
                } else {
                        $server->setClass($this->class);
                }

                // 
                // This is where we actually handle the request. If a called
                // method throws, then convert the exception to SOAP fault
                // object that is propagated to SOAP client.
                // 
                // See http://www.w3.org/TR/soap12-part1/#faultcodes
                //
                try {
                        $server->handle();
                } catch (\Exception $exception) {
                        $server->fault("Receiver", $exception->getMessage());
                        throw $exception;       // Handle exception upstream
                }
        }

}
