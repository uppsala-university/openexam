<?php

namespace OpenExam\Models;

use OpenExam\Tests\Phalcon\TestModel;

class MyModel extends ModelBase
{

        public function initialize()
        {
                parent::initialize();
        }

}

/**
 * Generated by PHPUnit_SkeletonGenerator 1.2.1 on 2014-09-19 at 03:15:59.
 */
class ModelBaseTest extends TestModel
{

        public function __construct()
        {
                parent::__construct(new MyModel());
        }

        /**
         * @covers OpenExam\Models\ModelBase::initialize
         * @group model
         */
        public function testInitialize()
        {
                parent::testInitialize();
        }

        /**
         * @covers OpenExam\Models\ModelBase::getName
         * @group model
         */
        public function testGetName()
        {
                $expect = "mymodel";
                $actual = $this->object->getName();
                self::assertNotNull($actual);
                self::assertEquals($expect, $actual);
        }

}