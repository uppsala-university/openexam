<?php

/*
 * Copyright (C) 2014-2018 The OpenExam Project
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
 */

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
