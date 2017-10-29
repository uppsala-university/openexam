<?php

namespace OpenExam\Library\Monitor\Diagnostics;

use OpenExam\Tests\Phalcon\TestCase;

/**
 * @author Anders Lövgren (Computing Department at BMC, Uppsala University)
 */
class DatabaseTest extends TestCase
{

        /**
         * @var Database
         */
        protected $object;

        /**
         * Sets up the fixture, for example, opens a network connection.
         * This method is called before a test is executed.
         */
        protected function setUp()
        {
                $this->object = new Database();
        }

        /**
         * Tears down the fixture, for example, closes a network connection.
         * This method is called after a test is executed.
         */
        protected function tearDown()
        {
                
        }

        /**
         * @covers OpenExam\Library\Monitor\Diagnostics\Database::getResult
         * @group monitor
         */
        public function testGetResult()
        {
                // 
                // Should be all success until tested first time:
                // 
                $actual = $this->object->getResult();
                $expect = array(
                        'dbread'  => array(
                                'online'  => true,
                                'working' => true
                        ),
                        'dbwrite' => array(
                                'online'  => true,
                                'working' => true
                        ),
                        'dbaudit' => array(
                                'online'  => true,
                                'working' => true
                        )
                );

                self::assertNotNull($actual);
                self::assertTrue(is_array($actual));
                self::assertEquals($expect, $actual);

                $this->object->isOnline();
                $this->object->isWorking();

                // 
                // Assuming dbread/dbwrite is on localhost:
                // 
                $actual = $this->object->getResult();
                $expect = array(
                        'dbread'  => array(
                                'online'  => array(
                                        '127.0.0.1' => true
                                ),
                                'working' => true
                        ),
                        'dbwrite' => array(
                                'online'  => array(
                                        '127.0.0.1' => true
                                ),
                                'working' => true
                        ),
                        'dbaudit' => array(
                                'online'  => array(
                                        '127.0.0.1' => true
                                ),
                                'working' => true
                        )
                );

                self::assertNotNull($actual);
                self::assertTrue(is_array($actual));
                self::assertEquals($expect, $actual);
        }

        /**
         * @covers OpenExam\Library\Monitor\Diagnostics\Database::isOnline
         * @group monitor
         */
        public function testIsOnline()
        {
                $expect = true;
                $actual = $this->object->isOnline();

                self::assertNotNull($actual);
                self::assertEquals($expect, $actual);
        }

        /**
         * @covers OpenExam\Library\Monitor\Diagnostics\Database::isWorking
         * @group monitor
         */
        public function testIsWorking()
        {
                $expect = true;
                $actual = $this->object->isOnline();

                self::assertNotNull($actual);
                self::assertEquals($expect, $actual);
        }

        /**
         * @covers OpenExam\Library\Monitor\Diagnostics\Database::hasFailed
         * @group monitor
         */
        public function testHasFailed()
        {
                $expect = false;
                $actual = $this->object->hasFailed();

                self::assertNotNull($actual);
                self::assertEquals($expect, $actual);

                $this->object->isOnline();
                $this->object->isWorking();

                $expect = false;
                $actual = $this->object->hasFailed();

                self::assertNotNull($actual);
                self::assertEquals($expect, $actual);
        }

}
