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
 * Test output handler.
 * @author Anders Lövgren (QNET/BMC CompDept)
 */
class TestLogger
{

        /**
         * @var \Phalcon\Config
         */
        private $config;
        /**
         * @var \Phalcon\Logger
         */
        private $logger;

        /**
         * Constructor.
         * @param \Phalcon\Config $config
         * @param \Phalcon\Logger $logger
         */
        public function __construct($config, $logger)
        {
                $this->config = $config;
                $this->logger = $logger;
        }

        /**
         * Output message.
         * @param string|array $trace The origin method.
         * @param string $status The status symbol (e.g. '+').
         * @param string|array $args The output arguments.
         */
        public function output($trace, $status, $args)
        {
                if (is_array($trace)) {
                        $method = $trace['class'] . '::' . $trace['function'];
                } else {
                        $method = $trace;
                }
                if (is_array($args)) {
                        $format = array_shift($args);
                        $message = vsprintf($format, $args);
                } else {
                        $message = $args;
                }
                if ($this->config->phpunit->logging) {
                        printf("%s: (%s) %s\n", $method, $status, $message);
                }
                if ($this->logger->phpunit) {
                        switch ($status) {
                                case '+':
                                case 'i':
                                        $this->logger->phpunit->info(sprintf("%s: %s", $method, $message));
                                        break;
                                case '!':
                                        $this->logger->phpunit->warning(sprintf("%s: %s", $method, $message));
                                        break;
                                case '-':
                                        $this->logger->phpunit->error(sprintf("%s: %s", $method, $message));
                                        break;
                        }
                }
        }

}

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
        /**
         * @var TestLogger 
         */
        private static $logger;

        public function __construct($name = NULL, array $data = array(), $dataName = '')
        {
                parent::__construct($name, $data, $dataName);
                $this->setDI(PhalconDI::getDefault());

                if (!isset(self::$logger)) {
                        self::$logger = new TestLogger($this->config, $this->logger);
                }

                if ($this->config->phpunit->logging) {
                        parent::setOutputCallback(new LoggingCallback(
                            $this->config->phpunit->logfile, $this->config->phpunit)
                        );
                }
        }

        /**
         * Get dependency injector.
         * @return Phalcon\DI
         */
        public function getDI()
        {
                return $this->di;
        }

        /**
         * Set dependency injector.
         * @param Phalcon\DI $dependencyInjector
         */
        public function setDI($dependencyInjector)
        {
                $this->di = $dependencyInjector;
                PhalconDI::setDefault($dependencyInjector);
        }

        public function __get($name)
        {
                if ($this->di->has($name)) {
                        return $this->di->get($name);
                }
        }

        /**
         * Output message.
         * @param string|array $trace The origin method.
         * @param string $status The status symbol (e.g. '+').
         * @param string|array $args The output arguments.
         */
        private static function output($trace, $status, $args)
        {
                self::$logger->output($trace, $status, $args);
        }

        /**
         * Output a success message.
         * @param string $format The format string.
         */
        public static function success($format)
        {
                $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2)[1];
                if (func_num_args() > 1) {
                        self::output($trace, "+", func_get_args());
                } else {
                        self::output($trace, "+", $format);
                }
        }

        /**
         * Output a info message.
         * @param string $format The format string.
         */
        public static function info($format)
        {
                $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2)[1];
                if (func_num_args() > 1) {
                        self::output($trace, "i", func_get_args());
                } else {
                        self::output($trace, "i", $format);
                }
        }

        /**
         * Output a warning message.
         * @param string $format The format string.
         */
        public static function warn($format)
        {
                $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2)[1];
                if (func_num_args() > 1) {
                        self::output($trace, "!", func_get_args());
                } else {
                        self::output($trace, "!", $format);
                }
        }

        /**
         * Fail this test.
         * 
         * Accepted and recognized arguments are exception, method name
         * and format string with optional arguments. The format string
         * has to be the last argument.
         * 
         * <code>
         * self::error($exception);     // recommended<br/>
         * self::error($exception, "failed in %s:%d", __FILE__, __LINE__);<br/>
         * self::error("failed in %s:%d", __FILE__, __LINE__);<br/>
         * self.:error("failed in func()");<br/>
         * </code>
         */
        public static function error($message = "")
        {
                $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2)[1];

                $message = "";
                if (func_num_args() > 0) {
                        $args = func_get_args();
                        while (($arg = array_shift($args)) != null) {
                                if ($arg instanceof \Exception) {
                                        $exception = $arg;
                                } else {
                                        $format = $arg;
                                        break;
                                }
                        }
                }
                if (isset($format)) {
                        $message = vsprintf($format, $args);
                }
                if (isset($exception)) {
                        $message .= sprintf(" [%s(%s:%d): %s]", get_class($exception), basename($exception->getFile()), $exception->getLine(), $exception->getMessage());
                }

                self::output($trace, "-", trim($message));
                self::fail(trim($message));
        }

}
