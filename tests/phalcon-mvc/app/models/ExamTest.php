<?php

namespace OpenExam\Models;

use Exception;
use OpenExam\Tests\Phalcon\TestModel;
use stdClass;

/**
 * Generated by PHPUnit_SkeletonGenerator 1.2.1 on 2014-09-15 at 19:03:31.
 * 
 * @author Anders Lövgren (Computing Department at BMC, Uppsala University)
 */
class ExamTest extends TestModel
{

        public function __construct()
        {
                parent::__construct(new Exam());
        }

        /**
         * @group model
         */
        public function testRelations()
        {
                $exam = Exam::findFirst();

                self::assertNotEquals($exam->contributors->count(), 0);
                self::assertNotEquals($exam->decoders->count(), 0);
                self::assertNotEquals($exam->invigilators->count(), 0);
                self::assertNotEquals($exam->questions->count(), 0);
                self::assertNotEquals($exam->students->count(), 0);
                self::assertNotEquals($exam->topics->count(), 0);

                self::assertTrue(count($exam->contributors) > 0);
                self::assertTrue(count($exam->decoders) > 0);
                self::assertTrue(count($exam->invigilators) > 0);
                self::assertTrue(count($exam->questions) > 0);
                self::assertTrue(count($exam->students) > 0);
                self::assertTrue(count($exam->topics) > 0);
        }

        /**
         * @group model
         */
        public function testProperties()
        {
                $values = array(
                        'creator' => 'user1',
                        'orgunit' => 'orgunit1',
                        'grades'  => json_encode(array('data' => array('key1' => 'val1'))),
                        'name'    => 'name1'
                );

                try {
                        $helper = new TestModel(new Exam());
                        $helper->tryPersist();
                        self::fail("Excepted constraint violation exception");
                } catch (Exception $exception) {
                        // Expected exception
                }

                try {
                        $helper = new TestModel(new Exam());
                        $helper->tryPersist($values);
                } catch (Exception $exception) {
                        self::fail($exception);
                }

                $helper->checkDefaults(array(
                        'details'  => 3,
                        'decoded'  => false,
                        'testcase' => false,
                        'lockdown' => false
                ));

                $values = array(
                        'updated' => date('Y-m-d H:i:s'),
                        'created' => date('Y-m-d H:i:s'),
                        'details' => 3,
                        'creator' => 'user1',
                        'orgunit' => 'orgunit1',
                        'grades'  => json_encode(array('data' => (array('key1' => 'val1')))),
                        'name'    => 'name1'
                );
                try {
                        $helper = new TestModel(new Exam());
                        $helper->tryPersist($values);
                } catch (Exception $exception) {
                        self::fail($exception);
                }

                $values = array(
                        'updated'   => date('Y-m-d H:i:s'),
                        'created'   => date('Y-m-d H:i:s'),
                        'details'   => 3,
                        'name'      => 'Name1',
                        'descr'     => 'Description1',
                        'starttime' => date('Y-m-d H:i:s'),
                        'endtime'   => date('Y-m-d H:i:s'),
                        'creator'   => 'user1',
                        'decoded'   => true,
                        'orgunit'   => 'Orgunit1',
                        'grades'    => json_encode(array('data' => array())),
                        'testcase'  => true,
                        'lockdown'  => true
                );
                try {
                        $helper = new TestModel(new Exam());
                        $helper->tryPersist($values);
                } catch (Exception $exception) {
                        self::fail($exception);
                }
        }

        /**
         * @covers OpenExam\Models\Exam::getSource
         * @group model
         */
        public function testGetSource()
        {
                $expect = "exams";
                $actual = $this->object->getSource();
                self::assertNotNull($actual);
                self::assertEquals($expect, $actual);
        }

}