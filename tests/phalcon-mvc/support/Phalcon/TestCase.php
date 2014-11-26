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

use OpenExam\Library\Security\User;
use Phalcon\Config;
use Phalcon\DI as PhalconDI;
use Phalcon\DI\InjectionAwareInterface;
use Phalcon\Logger;

/**
 * Test output handler.
 * @author Anders Lövgren (QNET/BMC CompDept)
 */
class TestLogger
{

        /**
         * @var Config
         */
        private $config;
        /**
         * @var Logger
         */
        private $logger;

        /**
         * Constructor.
         * @param Config $config
         * @param Logger $logger
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
 * Setup test case logging.
 * 
 * Setup logging to system logs. This class also setup stdout capture logging,
 * if configured in the system config.
 * 
 * @author Anders Lövgren (Computing Department at BMC, Uppsala University)
 */
class TestLogging
{

        /**
         * The test case object.
         * @var TestCase 
         */
        private $testcase;
        /**
         * @var TestLogger 
         */
        private static $testlog;
        /**
         * @var Config
         */
        private $config;
        /**
         * @var Logger 
         */
        private $logger;

        /**
         * Constructor
         * @param TestCase $testcase The test case object.
         */
        public function __construct($testcase)
        {
                $this->testcase = $testcase;
                $this->config = $testcase->config;
                $this->logger = $testcase->logger;
        }

        /**
         * Setup test logging.
         * @return TestLogger
         */
        public function setup()
        {
                if (!isset(self::$testlog)) {
                        self::$testlog = new TestLogger($this->config, $this->logger);
                }

                if ($this->config->phpunit->logging) {
                        $this->testcase->setOutputCallback(new LoggingCallback(
                            $this->config->phpunit->logfile, $this->config->phpunit)
                        );
                }

                return self::$testlog;
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
         * The username (principal) of test runner.
         * @var string 
         */
        protected $caller;
        /**
         * @var TestLogger 
         */
        private static $logger;

        public function __construct($name = NULL, array $data = array(), $dataName = '')
        {
                parent::__construct($name, $data, $dataName);

                $this->setupContext();
                $this->setupLogging();

                $this->caller = $this->di->get('user')->getPrincipalName();
        }
        
        /**
         * Setup test case logging.
         */
        private function setupLogging()
        {
                $logging = new TestLogging($this);
                self::$logger = $logging->setup();
        }

        /**
         * Setup test case context.
         */
        private function setupContext()
        {
                $this->setDI(PhalconDI::getDefault());
        }

        /**
         * Get dependency injector.
         * @return PhalconDI
         */
        public function getDI()
        {
                return $this->di;
        }

        /**
         * Set dependency injector.
         * @param PhalconDI $dependencyInjector
         */
        public function setDI($dependencyInjector)
        {
                $this->di = $dependencyInjector;
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
