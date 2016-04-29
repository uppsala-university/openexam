<?php

namespace OpenExam\Library\Core;

use OpenExam\Tests\Phalcon\TestCase;

/**
 * Generated by PHPUnit_SkeletonGenerator on 2015-04-08 at 12:03:33.
 */
class SettingsTest extends TestCase
{

        /**
         * @var Settings
         */
        private $_object;

        /**
         * Sets up the fixture, for example, opens a network connection.
         * This method is called before a test is executed.
         */
        protected function setUp()
        {
                $this->_object = new Settings();
                $this->_object->clear();
        }

        /**
         * Tears down the fixture, for example, closes a network connection.
         * This method is called after a test is executed.
         */
        protected function tearDown()
        {
                $this->_object->delete();
        }
        
        /**
         * @covers OpenExam\Library\Core\Settings::__destruct
         * @group core
         */
        public function test__destruct()
        {
                $expect = time();
                self::assertNotNull($this->_object);

                // 
                // Update user setting and read back:
                // 
                $this->_object->set('destruct', $expect);
                $this->_object = null;   // Save to persistent storage.
                $this->_object = new Settings();
                self::assertNotNull($this->_object);

                $actual = $this->_object->get('destruct');
                self::assertEquals($expect, $actual);
        }

        /**
         * @covers OpenExam\Library\Core\Settings::set
         * @group core
         */
        public function testSet()
        {
                $expect = array('key' => 'val');
                $this->_object->set('data', $expect);
                $actual = $this->_object->get('data');

                self::assertNotNull($actual);
                self::assertEquals($expect, $actual);
        }

        /**
         * @covers OpenExam\Library\Core\Settings::has
         * @group core
         */
        public function testHas()
        {
                self::assertFalse($this->_object->has('key'));
                $this->_object->set('key', true);
                self::assertTrue($this->_object->has('key'));
                $this->_object->set('key', false);
                self::assertTrue($this->_object->has('key'));
        }

        /**
         * @covers OpenExam\Library\Core\Settings::get
         * @group core
         */
        public function testGet()
        {
                self::assertNull($this->_object->get('key'));
                $this->_object->set('key', true);
                self::assertNotNull($this->_object->get('key'));
                self::assertTrue(is_bool($this->_object->get('key')));
        }

        /**
         * @covers OpenExam\Library\Core\Settings::save
         * @group core
         */
        public function testSave()
        {
                $this->_object->set('key', true);
                $this->_object->save();
                $this->_object->read();
                self::assertNotNull($this->_object->get('key'));
                self::assertTrue(is_bool($this->_object->get('key')));
        }

        /**
         * @covers OpenExam\Library\Core\Settings::read
         * @group core
         */
        public function testRead()
        {
                $this->_object->read();
                $actual = $this->_object->data();
                self::assertNotNull($actual);
                self::assertTrue(is_array($actual));
        }

        /**
         * @covers OpenExam\Library\Core\Settings::clear
         * @group core
         */
        public function testClear()
        {
                $this->_object->set('key', true);
                $actual = $this->_object->data();
                self::assertNotNull($actual);
                self::assertTrue(is_array($actual));
                self::assertTrue(count($actual) == 1);

                $this->_object->clear();
                $actual = $this->_object->data();
                self::assertNotNull($actual);
                self::assertTrue(is_array($actual));
                self::assertTrue(count($actual) == 0);
        }

        /**
         * @covers OpenExam\Library\Core\Settings::delete
         * @group core
         */
        public function testDelete()
        {
                $expect = $this->_object->data();
                $this->_object->delete();
                $actual = $this->_object->data();
                
                self::assertNotNull($actual);
                self::assertTrue(is_array($actual));
                self::assertTrue(count($actual) == 0);
                self::assertEquals($expect, $actual);
        }

        /**
         * @covers OpenExam\Library\Core\Settings::data
         * @group core
         */
        public function testData()
        {
                $actual = $this->_object->data();
                self::assertNotNull($actual);
                self::assertTrue(is_array($actual));
                self::assertTrue(count($actual) == 0);
        }

}
