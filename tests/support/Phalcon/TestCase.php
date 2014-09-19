<?php

// 
// The source code is copyrighted, with equal shared rights, between the
// authors (see the file AUTHORS) and the OpenExam project, Uppsala University 
// unless otherwise explicit stated elsewhere.
// 
// File:    PhalconUnitTest.php
// Created: 2014-09-01 16:33:14
// 
// Author:  Anders Lövgren (Computing Department at BMC, Uppsala University)
// 

namespace OpenExam\Tests\Phalcon;

use Phalcon\DI\InjectionAwareInterface,
    Phalcon\DI as PhalconDI;

/**
 * Provides dependency injection and service access for unit tests.
 * 
 * @author Anders Lövgren (Computing Department at BMC, Uppsala University)
 */
class TestCase extends \PHPUnit_Framework_TestCase implements InjectionAwareInterface
{

        /**
         * @var PhalconDI 
         */
        protected $di;

        public function __construct($name = NULL, array $data = array(), $dataName = '')
        {
                parent::__construct($name, $data, $dataName);
                $this->setDI(PhalconDI::getDefault());

                if ($this->config->phpunit->logging) {
                        parent::setOutputCallback(new LoggingCallback(
                            $this->config->phpunit->logfile, $this->config->phpunit)
                        );
                }
        }

        public function getDI()
        {
                return $this->di;
        }

        public function setDI($dependencyInjector)
        {
                $this->di = $dependencyInjector;
                PhalconDI::setDefault($dependencyInjector);
        }

        public function __get($name)
        {
                if ($this->di->has($name)) {
                        return $this->di->get($name);
                } else {
                        return parent::$name;
                }
        }

}
