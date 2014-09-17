<?php

namespace OpenExam\Models;

use Exception;
use OpenExam\Tests\Phalcon\TestModel;

/**
 * Generated by PHPUnit_SkeletonGenerator 1.2.1 on 2014-09-15 at 20:22:33.
 * @author Anders Lövgren (Computing Department at BMC, Uppsala University)
 */
class TopicTest extends TestModel
{

        public function __construct()
        {
                parent::__construct(new Topic());
        }

        /**
         * @group model
         */
        public function testRelations()
        {
                $topic = Topic::findFirst();

                self::assertNotEquals($topic->exam->count(), 0);
                self::assertNotEquals($topic->questions->count(), 0);

                self::assertTrue(count($topic->exam) == 1);
                self::assertTrue(count($topic->questions) >= 0);
        }

        /**
         * @group model
         */
        public function testProperties()
        {
                $values = array(
                        'exam_id' => Exam::findFirst()->id,
                        'name'    => 'Name1'
                );

                try {
                        $helper = new TestModel(new Topic());
                        $helper->tryPersist();
                        self::fail("Excepted constraint violation exception");
                } catch (Exception $exception) {
                        // Expected exception
                }

                try {
                        $helper = new TestModel(new Topic());
                        $helper->tryPersist($values);
                } catch (Exception $exception) {
                        self::fail($exception);
                }

                $values = array(
                        'exam_id' => Exam::findFirst()->id,
                        'name'    => 'Name1',
                        'grades'  => '// Ooh, really missing C++ ;-)',
                        'depend'  => 'Depend1'
                );

                try {
                        $helper = new TestModel(new Topic());
                        $helper->tryPersist($values);
                } catch (Exception $exception) {
                        self::fail($exception);
                }

                $values = array(
                        'exam_id'      => Exam::findFirst()->id,
                        'name'         => 'Name1',
                        'non_existing' => 666
                );
                try {
                        $helper = new TestModel(new Topic());
                        $helper->tryPersist($values);
                } catch (Exception $exception) {
                        self::fail("Unexcepted constraint violation exception");
                }
        }

        /**
         * @covers OpenExam\Models\Topic::getSource
         * @group model
         */
        public function testGetSource()
        {
                $expect = "topics";
                $actual = $this->object->getSource();
                self::assertNotNull($actual);
                self::assertEquals($expect, $actual);
        }

}