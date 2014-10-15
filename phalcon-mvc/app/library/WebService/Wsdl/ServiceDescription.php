<?php

// 
// The source code is copyrighted, with equal shared rights, between the
// authors (see the file AUTHORS) and the OpenExam project, Uppsala University 
// unless otherwise explicit stated elsewhere.
// 
// File:    SoapServiceDescription.php
// Created: 2014-10-10 03:38:57
// 
// Author:  Anders Lövgren (QNET/BMC CompDept)
// 

namespace OpenExam\Library\WebService\Wsdl;

/**
 * SOAP service description (WSDL).
 * @author Anders Lövgren (QNET/BMC CompDept)
 */
class ServiceDescription
{

        /**
         * Format output as HTML.
         */
        const FORMAT_HTML = 'html';
        /**
         * Format output as XML.
         */
        const FORMAT_XML = 'xml';

        /**
         * @var string 
         */
        private $class;
        /**
         * @var string 
         */
        private $location;
        /**
         * @var string 
         */
        private $namespace;
        /**
         * The WSDL generator.
         * @var Generator 
         */
        private $generator;

        /**
         * Constructor.
         * @param string $class The SOAP service class.
         * @param string $location Service location (endpoint).
         * @param string $namespace Service namespace.
         */
        public function __construct($class, $location = null, $namespace = null)
        {
                $this->class = $class;
                $this->location = $location;
                $this->namespace = $namespace;
        }

        /**
         * Set SOAP service location (endpoint).
         * @param string $location
         */
        public function setServiceLocation($location)
        {
                $this->location = $location;
        }

        /**
         * Set SOAP service namespace.
         * @param string $namespace
         */
        public function setNamespace($namespace)
        {
                $this->namespace = $namespace;
        }

        /**
         * Get SOAP service location (endpoint)
         * @return string
         */
        public function getServiceLocation()
        {
                return $this->location;
        }

        /**
         * Get service description generator.
         * @return Generator
         */
        public function getGenerator()
        {
                if (!isset($this->generator)) {
                        $this->generator = new Generator($this->class, $this->location, $this->namespace);
                }
                return $this->generator;
        }

        /**
         * Get service description (WSDL).
         * @param string $format The output format (html or xml).
         * @return string
         */
        private function getDescription($format)
        {
                $generator = $this->getGenerator();

                switch ($format) {
                        case self::FORMAT_HTML:
                                return $generator->getDocument()->saveHTML();
                        case self::FORMAT_XML:
                                return $generator->getDocument()->saveXML();
                }
        }

        /**
         * Get service description (WSDL).
         * @param string $format The output format (html or xml).
         * @return string
         */
        public function dump($format = self::FORMAT_XML)
        {
                return $this->getDescription($format);
        }

        /**
         * Send service description (WSDL) to stdout.
         * @param string $format The output format (html or xml).
         */
        public function send($format = self::FORMAT_HTML)
        {
                echo $this->getDescription($format);
        }

        /**
         * Save service description (WSDL) to file.
         * @param string $filename The destination file.
         * @param string $format The output format (html or xml).
         */
        public function save($filename, $format = self::FORMAT_XML)
        {
                file_put_contents($filename, $this->getDescription($format));
        }

}
