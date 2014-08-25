<?php

// 
// The source code is copyrighted, with equal shared rights, between the
// authors (see the file AUTHORS) and the OpenExam project, Uppsala University 
// unless otherwise explicit stated elsewhere.
// 
// File:    WsdlController.php
// Created: 2014-08-20 23:18:04
// 
// Author:  Anders Lövgren (QNET/BMC CompDept)
// 

namespace OpenExam\Controllers\Core;

/**
 * Handles WSDL for the core SOAP service.
 *
 * @author Anders Lövgren (QNET/BMC CompDept)
 */
class WsdlController extends \OpenExam\Controllers\ControllerBase
{

        public function initialize()
        {
                parent::initialize();
                $this->view->disable();
        }

        public function wsdlAction()
        {
                $this->serviceAction();
        }

        public function xsdAction()
        {
                $this->typesAction();
        }

        public function serviceAction()
        {
                return $this->sendCacheFile("core-soap-service.wsdl", "application/wsdl+xml");
        }

        public function typesAction()
        {
                return $this->sendCacheFile("core-soap-types.xsd", "application/xsd");
        }

        private function sendCacheFile($file, $type)
        {
                $config = $this->di->get('config');
                
                $tfile = sprintf("%s/%s", $config->application->schemasDir, $file);
                $cfile = sprintf("%s/%s", $config->application->cacheDir, $file);

                if (!file_exists($cfile)) {
                        $this->saveCacheFile($tfile, $cfile);
                }

                $this->response->setContentType($type);
                $this->response->setContent(file_get_contents($cfile));

                return $this->response;
        }

        private function saveCacheFile($source, $dest)
        {
                $router = $this->di->get('router')->getRouteByName('core-soap');
                $location = sprintf("%s://%s/%s", $this->request->getScheme(), $this->request->getServerAddress(), $router->getPattern());

                $content = file_get_contents($source);
                $content = str_replace("@SOAP_ADDRESS_LOCATION@", $location, $content);

                file_put_contents($dest, $content);
        }

}
