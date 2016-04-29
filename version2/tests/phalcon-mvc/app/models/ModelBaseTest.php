<?php

namespace OpenExam\Models;

use OpenExam\Models\ModelBase;
use OpenExam\Tests\Phalcon\TestCase;

class ExampleModel extends ModelBase
{

        public function initialize()
        {
                parent::initialize();
        }

}

/**
 * Generated by PHPUnit_SkeletonGenerator 1.2.1 on 2014-09-19 at 03:15:59.
 * @author Anders Lövgren (Computing Department at BMC, Uppsala University)
 */
class ModelBaseTest extends TestCase
{

        private $_object;

        public function setUp()
        {
                $this->_object = new ExampleModel();
        }

        /**
         * @covers OpenExam\Models\ModelBase::initialize
         * @group model
         */
        public function testInitialize()
        {
                $this->_object->initialize();
        }

        /**
         * @covers OpenExam\Models\ModelBase::getResourceName
         * @group model
         */
        public function testGetResourceName()
        {
                $expect = 'examplemodel';
                $actual = $this->_object->getResourceName();
                self::assertNotNull($actual);
                self::assertEquals($expect, $actual);
        }

        /**
         * @covers OpenExam\Models\ModelBase::beforeValidationOnUpdate
         * @group model
         */
        public function testBeforeValidationOnUpdate()
        {
                $model1 = Student::findFirst();

                $model2 = new Student();
                $model2->code = $model1->code;
                $model2->exam_id = $model1->exam_id;
                $model2->tag = $model1->tag;
                $model2->user = $model1->user;

                $expect = true;
                $actual = $model2->create();
                self::assertEquals($expect, $actual);

                // 
                // Test that unset required attributes are populated:
                // 
                $username = sprintf('testBeforeValidationOnUpdate@%s', $this->config->user->domain);
                $model2->user = $username;
                $model2->code = null;
                $model2->exam_id = null;

                $expect = true;
                $actual = $model2->update();
                self::assertEquals($expect, $actual);

                self::assertNotNull($model2->code);
                self::assertNotEquals(0, $model2->exam_id);

                self::assertEquals($model2->exam_id, $model1->exam_id);
                self::assertEquals($model2->code, $model1->code);
                self::assertEquals($model2->user, $username);
                self::assertEquals($model2->tag, $model1->tag);

                // 
                // Test that present values in the model is preserved:
                // 
                $expect = 'TAG';
                $model2->tag = $expect;
                $model2->update();

                $model2->code = 'X1Y2Z3';
                $model2->update();

                $model3 = Student::findFirstById($model2->id);
                $actual = $model3->tag;

                self::assertEquals($expect, $actual);
        }

}
