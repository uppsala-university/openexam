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
