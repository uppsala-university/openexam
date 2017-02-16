<?php

// 
// The source code is copyrighted, with equal shared rights, between the
// authors (see the file AUTHORS) and the OpenExam project, Uppsala University 
// unless otherwise explicit stated elsewhere.
// 
// File:    CheckTest.php
// Created: 2017-02-15 18:06:56
// 
// Author:  Anders Lövgren (Computing Department at BMC, Uppsala University)
// 

namespace OpenExam\Library\Core\Exam;

use OpenExam\Models\Access;
use OpenExam\Models\Exam;
use OpenExam\Models\Question;
use OpenExam\Models\Student;
use OpenExam\Models\Topic;
use OpenExam\Tests\Phalcon\TestCase;

/**
 * Unit test for Check class.
 *
 * @author Anders Lövgren (Computing Department at BMC, Uppsala University)
 */
class CheckTest extends TestCase
{

        /**
         * @var Check 
         */
        private $_check;
        /**
         * @var Exam 
         */
        private $_exam;
        /**
         * @var array 
         */
        private static $_data;

        /**
         * Sets up the fixture, for example, opens a network connection.
         * This method is called before a test is executed.
         */
        protected function setUp()
        {
                parent::setUp();

                self::$_data = array(
                        'exam'     => array(
                                'name'     => 'N',
                                'creator'  => $this->_caller,
                                'orgunit'  => 'O',
                                'grades'   => 'G',
                                'lockdown' => (object) array(
                                        'enable' => false
                                )
                        ),
                        'student'  => array(
                                'exam_id' => 0,
                                'user'    => $this->_caller,
                                'code'    => '1234ABCD'
                        ),
                        'topic'    => array(
                                'exam_id'   => 0,
                                'name'      => 'Topic 1',
                                'randomize' => 0
                        ),
                        'question' => array(
                                'exam_id'  => 0,
                                'topic_id' => 0,
                                'score'    => 1.0,
                                'name'     => 'Question 1',
                                'quest'    => 'Question text',
                                'user'     => $this->_caller
                        ),
                        'access'   => array(
                                'exam_id' => 0,
                                'name'    => 'Name 1',
                                'addr'    => '192.168.2.1'
                        )
                );

                $this->addExam();
                $this->_check = new Check($this->_exam);
        }

        /**
         * Tears down the fixture, for example, closes a network connection.
         * This method is called after a test is executed.
         */
        protected function tearDown()
        {
                if (isset($this->_exam)) {
                        $this->_exam->delete();
                }
        }

        private function addExam()
        {
                $this->_exam = new Exam();
                $this->_exam->create(self::$_data['exam']);
        }

        private function addStudent()
        {
                self::$_data['student']['exam_id'] = $this->_exam->id;
                $this->student = new Student();
                $this->student->create(self::$_data['student']);
        }

        private function addTopic()
        {
                self::$_data['topic']['exam_id'] = $this->_exam->id;
                $this->topic = new Topic();
                $this->topic->create(self::$_data['topic']);
        }

        private function addQuestion()
        {
                self::$_data['question']['exam_id'] = $this->_exam->id;
                self::$_data['question']['topic_id'] = $this->topic->id;
                $this->question = new Question();
                $this->question->create(self::$_data['question']);
        }

        private function addAccess()
        {
                self::$_data['access']['exam_id'] = $this->_exam->id;
                $this->access = new Access();
                $this->access->create(self::$_data['access']);
        }

        /**
         * @covers OpenExam\Library\Core\Exam\Check::getSecurity
         * @group core
         */
        public function testGetSecurity()
        {
                $expect = Check::SECURITY_NONE;
                $actual = $this->_check->getSecurity();
                self::assertTrue(is_int($actual));
                self::assertNotNull($actual);
                self::assertEquals($expect, $actual);

                $this->_exam->lockdown->enable = true;

                $expect = Check::SECURITY_LOCKDOWN;
                $actual = $this->_check->getSecurity();
                self::assertTrue(is_int($actual));
                self::assertNotNull($actual);
                self::assertEquals($expect, $actual);

                $this->addAccess();
                $this->_exam->lockdown->enable = false;

                $expect = Check::SECURITY_LOCATION;
                $actual = $this->_check->getSecurity();
                self::assertTrue(is_int($actual));
                self::assertNotNull($actual);
                self::assertEquals($expect, $actual);

                $this->_exam->lockdown->enable = true;

                $expect = Check::SECURITY_FULL;
                $actual = $this->_check->getSecurity();
                self::assertTrue(is_int($actual));
                self::assertNotNull($actual);
                self::assertEquals($expect, $actual);
        }

        /**
         * @covers OpenExam\Library\Core\Exam\Check::getStatus
         * @group core
         */
        public function testGetStatus()
        {
                $expect = Check::STATUS_NOT_READY;
                $actual = $this->_check->getStatus();
                self::assertTrue(is_int($actual));
                self::assertNotNull($actual);
                self::assertEquals($expect, $actual);

                $this->addTopic();
                $this->addQuestion();
                $this->addStudent();

                $expect = Check::STATUS_NOT_READY;
                $actual = $this->_check->getStatus();
                self::assertTrue(is_int($actual));
                self::assertNotNull($actual);
                self::assertEquals($expect, $actual);

                $this->_exam->starttime = '2017-02-16';

                $expect = Check::STATUS_NOT_READY;
                $actual = $this->_check->getStatus();
                self::assertTrue(is_int($actual));
                self::assertNotNull($actual);
                self::assertEquals($expect, $actual);

                $this->_exam->published = true;

                // 
                // Need attention because lockdown is disabled.
                // 
                $expect = Check::STATUS_NEED_ATTENTION;
                $actual = $this->_check->getStatus();
                self::assertTrue(is_int($actual));
                self::assertNotNull($actual);
                self::assertEquals($expect, $actual);

                $this->_exam->lockdown->enable = true;

                // 
                // Need attention because locations are missing.
                // 
                $expect = Check::STATUS_NEED_ATTENTION;
                $actual = $this->_check->getStatus();
                self::assertTrue(is_int($actual));
                self::assertNotNull($actual);
                self::assertEquals($expect, $actual);

                $this->addAccess();

                // 
                // All requirements are fullfilled.
                // 
                $expect = Check::STATUS_IS_READY;
                $actual = $this->_check->getStatus();
                self::assertTrue(is_int($actual));
                self::assertNotNull($actual);
                self::assertEquals($expect, $actual);
        }

        /**
         * @covers OpenExam\Library\Core\Exam\Check::hasName
         * @group core
         */
        public function testHasName()
        {
                $this->_exam->name = null;
                
                $expect = false;
                $actual = $this->_check->hasName();
                self::assertTrue(is_bool($actual));
                self::assertNotNull($actual);
                self::assertEquals($expect, $actual);

                $this->_exam->name = '';
                
                $expect = false;
                $actual = $this->_check->hasName();
                self::assertTrue(is_bool($actual));
                self::assertNotNull($actual);
                self::assertEquals($expect, $actual);

                $this->_exam->name = 'N';
                
                $expect = true;
                $actual = $this->_check->hasName();
                self::assertTrue(is_bool($actual));
                self::assertNotNull($actual);
                self::assertEquals($expect, $actual);
        }

        /**
         * @covers OpenExam\Library\Core\Exam\Check::hasQuestions
         * @group core
         */
        public function testHasQuestions()
        {
                $expect = false;
                $actual = $this->_check->hasQuestions();
                self::assertTrue(is_bool($actual));
                self::assertNotNull($actual);
                self::assertEquals($expect, $actual);

                $this->addTopic();
                $this->addQuestion();

                $expect = true;
                $actual = $this->_check->hasQuestions();
                self::assertTrue(is_bool($actual));
                self::assertNotNull($actual);
                self::assertEquals($expect, $actual);
        }

        /**
         * @covers OpenExam\Library\Core\Exam\Check::hasSecurity
         * @group core
         */
        public function testHasSecurity()
        {
                $expect = false;
                $actual = $this->_check->hasSecurity();
                self::assertTrue(is_bool($actual));
                self::assertNotNull($actual);
                self::assertEquals($expect, $actual);

                $this->_exam->lockdown->enable = true;

                $expect = false;
                $actual = $this->_check->hasSecurity();
                self::assertTrue(is_bool($actual));
                self::assertNotNull($actual);
                self::assertEquals($expect, $actual);

                $this->addAccess();

                $expect = true;
                $actual = $this->_check->hasSecurity();
                self::assertTrue(is_bool($actual));
                self::assertNotNull($actual);
                self::assertEquals($expect, $actual);
        }

        /**
         * @covers OpenExam\Library\Core\Exam\Check::hasStartTime
         * @group core
         */
        public function testHasStartTime()
        {
                $expect = false;
                $actual = $this->_check->hasStartTime();
                self::assertTrue(is_bool($actual));
                self::assertNotNull($actual);
                self::assertEquals($expect, $actual);

                $this->_exam->starttime = '2017-02-16';

                $expect = true;
                $actual = $this->_check->hasStartTime();
                self::assertTrue(is_bool($actual));
                self::assertNotNull($actual);
                self::assertEquals($expect, $actual);
        }

        /**
         * @covers OpenExam\Library\Core\Exam\Check::hasStudents
         * @group core
         */
        public function testHasStudents()
        {
                $expect = false;
                $actual = $this->_check->hasStudents();
                self::assertTrue(is_bool($actual));
                self::assertNotNull($actual);
                self::assertEquals($expect, $actual);

                $this->addStudent();

                $expect = true;
                $actual = $this->_check->hasStudents();
                self::assertTrue(is_bool($actual));
                self::assertNotNull($actual);
                self::assertEquals($expect, $actual);
        }

        /**
         * @covers OpenExam\Library\Core\Exam\Check::isPublished
         * @group core
         */
        public function testIsPublished()
        {
                $expect = false;
                $actual = $this->_check->isPublished();
                self::assertTrue(is_bool($actual));
                self::assertNotNull($actual);
                self::assertEquals($expect, $actual);

                $this->_exam->published = true;

                $expect = true;
                $actual = $this->_check->isPublished();
                self::assertTrue(is_bool($actual));
                self::assertNotNull($actual);
                self::assertEquals($expect, $actual);
        }

        /**
         * @covers OpenExam\Library\Core\Exam\Check::isReady
         * @group core
         */
        public function testIsReady()
        {
                $expect = false;
                $actual = $this->_check->isReady();
                self::assertTrue(is_bool($actual));
                self::assertNotNull($actual);
                self::assertEquals($expect, $actual);

                $this->addTopic();
                $this->addQuestion();
                $this->addStudent();

                $expect = false;
                $actual = $this->_check->isReady();
                self::assertTrue(is_bool($actual));
                self::assertNotNull($actual);
                self::assertEquals($expect, $actual);

                $this->_exam->starttime = '2017-02-16';

                $expect = false;
                $actual = $this->_check->isReady();
                self::assertTrue(is_bool($actual));
                self::assertNotNull($actual);
                self::assertEquals($expect, $actual);

                $this->_exam->published = true;

                $expect = false;
                $actual = $this->_check->isReady();
                self::assertTrue(is_bool($actual));
                self::assertNotNull($actual);
                self::assertEquals($expect, $actual);

                $this->_exam->lockdown->enable = true;

                $expect = false;
                $actual = $this->_check->isReady();
                self::assertTrue(is_bool($actual));
                self::assertNotNull($actual);
                self::assertEquals($expect, $actual);

                $this->addAccess();

                $expect = true;
                $actual = $this->_check->isReady();
                self::assertTrue(is_bool($actual));
                self::assertNotNull($actual);
                self::assertEquals($expect, $actual);
        }

}
