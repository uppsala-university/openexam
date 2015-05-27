<?php

// 
// The source code is copyrighted, with equal shared rights, between the
// authors (see the file AUTHORS) and the OpenExam project, Uppsala University 
// unless otherwise explicit stated elsewhere.
// 
// File:    State.php
// Created: 2014-09-30 21:58:54
// 
// Author:  Anders Lövgren (QNET/BMC CompDept)
// 

namespace OpenExam\Library\Core\Exam;

use OpenExam\Models\Exam;

/**
 * Represents the exam state.
 * 
 * <code>
 * $state = new State($exam);
 * if($state->has(State::DECODABLE)) {
 *      // decode examination...
 * }
 * if($state->getState() & State::DECODABLE) {
 *      // decode examination...
 * }
 * </code>
 * 
 * Two special cases arise based on starttime/endtime:
 * 
 * o) starttime == null -> The exam is considered to be a draft (not yet scheduled).
 * o) endtime   == null -> The exam is ongoing (running), but without an ending.
 * 
 * @see http://it.bmc.uu.se/andlov/proj/edu/openexam/manual/workflow.php
 * @author Anders Lövgren (Computing Department at BMC, Uppsala University)
 */
class State
{

        /**
         * Still possible to contribute questions.
         */
        const CONTRIBUTABLE = 0x0001;
        /**
         * Examination started. New students can still be added.
         */
        const EXAMINATABLE = 0x0002;
        /**
         * Examination finished. Not yet decoded.
         */
        const CORRECTABLE = 0x0004;
        /**
         * Examination can be decoded.
         */
        const DECODABLE = 0x0008;
        /**
         * Examination has been decoded.
         */
        const DECODED = 0x0010;
        /**
         * Examination is still fully editable.
         */
        const EDITABLE = 0x0020;
        /**
         * This examination can be deleted (e.g. no answers).
         */
        const DELETABLE = 0x0040;
        /**
         * This examination can be reused (not seen yet).
         */
        const REUSABLE = 0x0080;
        /**
         * This examination has not yet started.
         */
        const UPCOMING = 0x0100;
        /**
         * The examination is ongoing.
         */
        const RUNNING = 0x0200;
        /**
         * The examination has finished.
         */
        const FINISHED = 0x0400;
        /**
         * This examination is a testcase.
         */
        const TESTCASE = 0x0800;
        /**
         * This examination requires lockdown.
         */
        const LOCKDOWN = 0x1000;
        /**
         * Examination is a draft (not yet scheduled).
         */
        const DRAFT = 0x2000;
        /**
         * Examination has been published.
         */
        const PUBLISHED = 0x4000;

        /**
         * @var Exam 
         */
        private $exam;
        /**
         * Bit mask of examination state.
         * @var int 
         */
        private $state;
        /**
         * State flags (e.g. contributable, editable, upcoming).
         * @var array 
         */
        private $flags;

        /**
         * Constructor.
         * @param Exam $exam The examination object.
         */
        public function __construct($exam)
        {
                $this->exam = $exam;
                $this->setState();
        }

        /**
         * Refresh examination state.
         */
        public function refresh()
        {
                $this->setState();
        }

        /**
         * Set examination state.
         */
        private function setState()
        {
                if (isset($this->flags)) {
                        unset($this->flags);    // Called from refresh
                }

                if ($this->exam->decoded) {
                        $this->state = self::DECODED | self::DECODABLE | self::FINISHED;
                } elseif (!isset($this->exam->starttime)) {
                        $this->state = self::CONTRIBUTABLE | self::EXAMINATABLE | self::EDITABLE | self::DRAFT;
                } else {
                        $this->state = 0;

                        $stime = strtotime($this->exam->starttime);
                        $etime = strtotime($this->exam->endtime);
                        $ctime = time();

                        if ($ctime < $stime) {                  // Before exam begins
                                $this->state = self::CONTRIBUTABLE | self::EXAMINATABLE | self::EDITABLE | self::UPCOMING;
                        } elseif ($etime == 0) {                // Has starttime set, but no endtime -> never ending exam
                                $this->state = self::EXAMINATABLE | self::RUNNING;
                        } elseif ($ctime < $etime) {            // After exam begin, but before its finished
                                $this->state = self::EXAMINATABLE | self::RUNNING;
                        } elseif (!$this->hasAnswers()) {       // Unseen exam can be reused
                                $this->state = self::REUSABLE | self::DELETABLE | self::FINISHED;
                        } elseif ($this->isCorrected()) {       // After exam has finished
                                $this->state = self::CORRECTABLE | self::FINISHED | self::DECODABLE;
                        } else {
                                $this->state = self::CORRECTABLE | self::FINISHED;
                        }
                }
                if ($this->exam->testcase) {
                        $this->state |= self::TESTCASE | self::DELETABLE;
                }
                if ($this->exam->lockdown->enable) {
                        $this->state |= self::LOCKDOWN;
                }
                if ($this->exam->published) {
                        $this->state |= self::PUBLISHED;
                } else {
                        $this->state |= self::DELETABLE;
                }

                if ($this->hasAnswers() == false) {     // Contributable and resuable until first seen
                        $this->state |= self::CONTRIBUTABLE | self::EXAMINATABLE | self::EDITABLE | self::REUSABLE;
                }
        }

        /**
         * Get examination state.
         * @return int
         */
        public function getState()
        {
                return $this->state;
        }

        /**
         * Test if flag is set.
         * @param int $flag One of the class constants.
         * @return bool
         */
        public function has($flag)
        {
                return ($this->state & $flag) != 0;
        }

        /**
         * Returns true if examination is corrected.
         */
        private function isCorrected()
        {
                $connection = $this->exam->getReadConnection();
                $resultset = $connection->query("
                SELECT  a.id
                FROM    questions q, students s, answers a
                        LEFT JOIN results r ON 
                        (a.id = r.answer_id AND r.correction NOT IN ('waiting','partial'))
                WHERE   s.exam_id = :examid AND
                        s.id = a.student_id AND
                        q.id = a.question_id AND 
                        q.status != 'removed' AND 
                        a.answered = 'Y' AND
                        r.id IS NULL", array('examid' => $this->exam->id));
                return $resultset->numRows() == 0;
        }

        /**
         * Returns true if examination has answers.
         */
        private function hasAnswers()
        {
                $connection = $this->exam->getReadConnection();
                $resultset = $connection->query("
                SELECT  a.id
                FROM    questions q, students s, answers a
                        LEFT JOIN results r ON a.id = r.answer_id
                WHERE   s.exam_id = :examid AND
                        s.id = a.student_id AND
                        q.id = a.question_id AND 
                        q.status != 'removed' AND 
                        a.answered = 'Y'", array('examid' => $this->exam->id));
                return $resultset->numRows() != 0;
        }

        /**
         * Get exam state as array.
         * 
         * Returns the string
         * @return array 
         */
        public function getFlags()
        {
                if (isset($this->flags)) {
                        return $this->flags;
                }

                $this->flags = array();
                $reflection = new \ReflectionObject($this);
                foreach ($reflection->getConstants() as $name => $value) {
                        if ($this->has($value)) {
                                $this->flags[] = strtolower($name);
                        }
                }

                return $this->flags;
        }

}
