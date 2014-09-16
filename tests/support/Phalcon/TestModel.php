<?php

// 
// The source code is copyrighted, with equal shared rights, between the
// authors (see the file AUTHORS) and the OpenExam project, Uppsala University 
// unless otherwise explicit stated elsewhere.
// 
// File:    ModelPropertyTest.php
// Created: 2014-09-15 09:41:48
// 
// Author:  Anders Lövgren (Computing Department at BMC, Uppsala University)
// 

namespace OpenExam\Tests\Phalcon;

use Exception;
use Phalcon\Mvc\Model;

/**
 * Helper class for testing models.
 * 
 * <code>
 * // The model to test:
 * $model = new SomeModel();
 * 
 * // Set expected default values:
 * $default = array(
 *      'prop1' => 'string',
 *      'prop2' => 123
 * );
 * // Set referenced values:
 * $values = array(
 *      'question_id' => 456
 *      'exam_id'     => 789
 * );
 * 
 * // Test all properties:
 * $helper = new TestModel($model, $values, $default);
 * $helper->run();
 * </code>
 * @author Anders Lövgren (Computing Department at BMC, Uppsala University)
 */
class TestModel extends TestCase
{

        /**
         * @var Model 
         */
        public $object;

        public function __construct($object)
        {
                parent::__construct();
                $this->object = $object;
        }

        /**
         * @group model
         */
        public function testInitialize()
        {
                $expect = 'dbread';
                $actual = $this->object->getReadConnectionService();
                self::assertNotNull($actual);
                self::assertEquals($actual, $expect);

                $expect = 'dbwrite';
                $actual = $this->object->getWriteConnectionService();
                self::assertNotNull($actual);
                self::assertEquals($actual, $expect);
        }

        /**
         * @group model
         * @param array $values Model payload
         * @throws Exception
         */
        public function tryPersist($values = array())
        {
                $this->object->assign($values);

                // 
                // Test before persist.
                // 
                foreach ($values as $name => $value) {
                        self::assertEquals($value, $this->object->$name);
                }

                // 
                // Test before/after hooks and validation:
                // 
                if ($this->object->create() == false) {
                        throw new Exception(print_r($this->object->getMessages(), true));
                }
                foreach ($values as $name => $value) {
                        self::assertEquals($value, $this->object->$name);
                }

                if ($this->object->update() == false) {
                        throw new Exception(print_r($this->object->getMessages(), true));
                }
                foreach ($values as $name => $value) {
                        self::assertEquals($value, $this->object->$name);
                }
                if ($this->object->delete() == false) {
                        throw new Exception(print_r($this->object->getMessages(), true));
                }
                foreach ($values as $name => $value) {
                        self::assertEquals($value, $this->object->$name);
                }
        }

        public function checkDefaults($default = array())
        {
                foreach ($default as $name => $value) {
                        self::assertEquals($value, $this->object->$name);
                }
        }

}
