<?php

// 
// The source code is copyrighted, with equal shared rights, between the
// authors (see the file AUTHORS) and the OpenExam project, Uppsala University 
// unless otherwise explicit stated elsewhere.
// 
// File:    Generator.php
// Created: 2014-10-15 14:40:48
// 
// Author:  Anders Lövgren (Computing Department at BMC, Uppsala University)
// 

namespace OpenExam\Library\WebService\Wsdl;

use WSDL_Gen as ExternalGenerator;
use const EXTERN_DIR;

require_once (EXTERN_DIR . 'WSDL_Gen.php');

/**
 * SOAP service description (WSDL) generator.
 *
 * @author Anders Lövgren (Computing Department at BMC, Uppsala University)
 */
class Generator extends ExternalGenerator
{

        /**
         * Get XML document object.
         * 
         * Returns the DOM tree document describing the SOAP service.
         * @return \DomDocument
         */
        public function getDocument()
        {
                $xmldoc = new \DomDocument("1.0");
                $root = $xmldoc->createElement('wsdl:definitions');
                $root->setAttributeNS('http://www.w3.org/2000/xmlns/', 'xmlns:xsd', 'http://www.w3.org/2001/XMLSchema');
                $root->setAttributeNS('http://www.w3.org/2000/xmlns/', 'xmlns:tns', $this->ns);
                $root->setAttributeNS('http://www.w3.org/2000/xmlns/', 'xmlns:soap-env', self::SCHEMA_SOAP);
                $root->setAttributeNS('http://www.w3.org/2000/xmlns/', 'xmlns:wsdl', self::SCHEMA_WSDL);
                $root->setAttributeNS('http://www.w3.org/2000/xmlns/', 'xmlns:soapenc', self::SOAP_SCHEMA_ENCODING);
                $root->setAttribute('targetNamespace', $this->ns);
                $this->addTypes($xmldoc, $root);
                $this->addMessages($xmldoc, $root);
                $this->addPortType($xmldoc, $root);
                $this->addBinding($xmldoc, $root);
                $this->addService($xmldoc, $root);

                $xmldoc->formatOutput = true;
                $xmldoc->appendChild($root);

                return $xmldoc;
        }

}
