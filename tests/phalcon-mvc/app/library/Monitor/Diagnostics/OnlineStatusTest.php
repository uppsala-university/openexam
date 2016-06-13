<?php

namespace OpenExam\Library\Monitor\Diagnostics;

use OpenExam\Tests\Phalcon\TestCase;

/**
 * @author Anders Lövgren (Computing Department at BMC, Uppsala University)
 */
class OnlineStatusTest extends TestCase
{

        /**
         * Test against localhost.
         */
        const HOSTNAME = 'localhost';
        /**
         * The localhost address.
         */
        const ADDRESS = '127.0.0.1';    // Hopefully ;-)

        /**
         * @var OnlineStatus
         */

        protected $object;

        /**
         * Sets up the fixture, for example, opens a network connection.
         * This method is called before a test is executed.
         */
        protected function setUp()
        {
                $this->object = new OnlineStatus(self::HOSTNAME);
        }

        /**
         * Tears down the fixture, for example, closes a network connection.
         * This method is called after a test is executed.
         */
        protected function tearDown()
        {
                
        }

        /**
         * @covers OpenExam\Library\Monitor\Diagnostics\OnlineStatus::getResult
         * @group monitor
         */
        public function testGetResult()
        {
                $expect = array();
                $actual = $this->object->getResult();
                self::assertNotNull($actual);
                self::assertEquals($expect, $actual);
        }

        /**
         * @covers OpenExam\Library\Monitor\Diagnostics\OnlineStatus::getHostname
         * @group monitor
         */
        public function testGetHostname()
        {
                $expect = self::HOSTNAME;
                $actual = $this->object->getHostname();
                self::assertNotEmpty($actual);
                self::assertEquals($expect, $actual);
        }

        /**
         * @covers OpenExam\Library\Monitor\Diagnostics\OnlineStatus::setHostname
         * @group monitor
         */
        public function testSetHostname()
        {
                $expect = "www.example.com";
                $this->object->setHostname($expect);
                $actual = $this->object->getHostname();
                self::assertNotEmpty($actual);
                self::assertEquals($expect, $actual);
        }

        /**
         * @covers OpenExam\Library\Monitor\Diagnostics\OnlineStatus::hasFailed
         * @group monitor
         */
        public function testHasFailed()
        {
                $expect = false;
                $this->object->checkStatus();
                $actual = $this->object->hasFailed();

                self::assertNotNull($actual);
                self::assertEquals($expect, $actual);
        }

        /**
         * @covers OpenExam\Library\Monitor\Diagnostics\OnlineStatus::getAddresses
         * @group monitor
         */
        public function testGetAddresses()
        {
                $expect = array();
                $actual = $this->object->getAddresses();

                self::assertNotNull($actual);
                self::assertEquals($expect, $actual);

                $this->object->checkStatus();

                $expect = array(self::ADDRESS);
                $actual = $this->object->getAddresses();

                self::assertNotNull($actual);
                self::assertEquals($expect, $actual);
        }

        /**
         * @covers OpenExam\Library\Monitor\Diagnostics\OnlineStatus::checkStatus
         * @group monitor
         */
        public function testCheckStatus()
        {
                // 
                // Resolve and ping localhost should always be working.
                // 
                $this->object->checkStatus();

                // 
                // Resolve errors should throw an exception.
                // 
                $this->setExpectedException(\OpenExam\Library\Monitor\Exception::class);
                $this->object->setHostname("www.local.dom");
                $this->object->checkStatus();
        }

        /**
         * @covers OpenExam\Library\Monitor\Diagnostics\OnlineStatus::isOnline
         * @group monitor
         */
        public function testIsOnline()
        {
                $expect = true;
                $actual = OnlineStatus::isOnline(self::HOSTNAME);

                self::assertNotNull($actual);
                self::assertEquals($expect, $actual);

                $expect = false;
                $actual = OnlineStatus::isOnline("www.local.dom");

                self::assertNotNull($actual);
                self::assertEquals($expect, $actual);
        }

}
