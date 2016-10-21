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

use OpenExam\Library\Database\Exception as DatabaseException;
use OpenExam\Models\Exam;
use Phalcon\Mvc\User\Component;

/**
 * Represents the exam state.
 * 
 * State:
 * ---------
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
 * Cache:
 * ---------
 * 
 * The answered and corrected properties are quite expensive to compute and
 * are therefor cached for 30 seconds to overcome slow query problems when
 * many students tries to read an exam. 
 * 
 * During this period the exam state might be inconsistent, but the impact 
 * should be minimal and the benefit that large to justify this. If problem
 * with decoding arise, then the corrected cache can be invalidated when a
 * answer result is saved.
 * 
 * @see http://it.bmc.uu.se/andlov/proj/edu/openexam/manual/workflow.php
 * @author Anders Lövgren (Computing Department at BMC, Uppsala University)
 */
class State extends Component
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
         * Examination has been answered.
         */
        const ANSWERED = 0x8000;
        /**
         * Lifetime of cached answered and corrected state.
         */
        const CACHE_LIFETIME = 30;

        /**
         * The cache key.
         * @var string
         */
        private $_ckey;
        /**
         * @var Exam 
         */
        private $_exam;
        /**
         * Bit mask of examination state.
         * @var int 
         */
        private $_state;
        /**
         * State flags (e.g. contributable, editable, upcoming).
         * @var array 
         */
        private $_flags;
        /**
         * This exam has been corrected.
         * @var bool 
         */
        private $_corrected;
        /**
         * This exam has at least one answer.
         * @var bool 
         */
        private $_answered;

        /**
         * Constructor.
         * @param Exam $exam The exam object.
         */
        public function __construct($exam)
        {
                $this->_exam = $exam;
                $this->_ckey = $this->createCacheKey();

                if ($this->hasCache()) {
                        $this->getCache();
                } else {
                        $this->setCache();
                }
        }

        /**
         * Check if cached data exists.
         * @return boolean
         */
        private function hasCache()
        {
                return $this->modelsCache->exists($this->_ckey, self::CACHE_LIFETIME);
        }

        /**
         * Get data from cache.
         */
        private function getCache()
        {
                $data = $this->modelsCache->get($this->_ckey, self::CACHE_LIFETIME);

                $this->_state = $data['state'];
                $this->_flags = $data['flags'];

                $this->_corrected = $data['corrected'];
                $this->_answered = $data['answered'];

                $data = null;
        }

        /**
         * Update object state and refresh cache.
         */
        private function setCache()
        {
                $this->refresh();

                $this->modelsCache->save($this->_ckey, array(
                        'state'     => $this->_state,
                        'flags'     => $this->_flags,
                        'corrected' => $this->_corrected,
                        'answered'  => $this->_answered
                    ), self::CACHE_LIFETIME);
        }

        /**
         * Refresh examination state.
         */
        public function refresh()
        {
                $this->setAnswered();
                $this->setCorrected();
                $this->setState();
                $this->setFlags();
        }

        /**
         * Get examination state.
         * @return int
         */
        public function getState()
        {
                return $this->_state;
        }

        /**
         * Get examination flags.
         * @return array
         */
        public function getFlags()
        {
                return $this->_flags;
        }

        /**
         * Check correction status.
         * @return bool True if exam is fully corrected.
         */
        public function isCorrected()
        {
                return $this->_corrected;
        }

        /**
         * Check answer status
         * @return bool True if examination has answers.
         */
        public function isAnswered()
        {
                return $this->_answered;
        }

        /**
         * Test if flag is set.
         * @param int $flag One of the class constants.
         * @return bool
         */
        public function has($flag)
        {
                return ($this->_state & $flag) != 0;
        }

        /**
         * Set exam corrected status.
         */
        private function setCorrected()
        {
                $this->_corrected = ($this->getUncorrected() == 0);
        }

        /**
         * Set exam answered status.
         */
        private function setAnswered()
        {
                $this->_answered = ($this->getAnswered() != 0);
        }

        /**
         * Set exam state.
         */
        private function setFlags()
        {
                if (isset($this->_flags)) {
                        return $this->_flags;
                }

                $this->_flags = array();
                $reflection = new \ReflectionObject($this);
                foreach ($reflection->getConstants() as $name => $value) {
                        if ($name == 'CACHE_LIFETIME') {
                                continue;
                        }
                        if ($this->has($value)) {
                                $this->_flags[] = strtolower($name);
                        }
                }
                $reflection = null;
        }

        /**
         * Set examination state.
         */
        private function setState()
        {
                if (isset($this->_flags)) {
                        unset($this->_flags);    // Called from refresh
                }

                if ($this->_exam->decoded) {
                        $this->_state = self::DECODED | self::DECODABLE | self::FINISHED;
                } elseif (!isset($this->_exam->starttime)) {
                        $this->_state = self::CONTRIBUTABLE | self::EXAMINATABLE | self::EDITABLE | self::DRAFT;
                } else {
                        $this->_state = 0;

                        $stime = strtotime($this->_exam->starttime);
                        $etime = strtotime($this->_exam->endtime);
                        $ctime = time();

                        if ($ctime < $stime) {                  // Before exam begins
                                $this->_state = self::CONTRIBUTABLE | self::EXAMINATABLE | self::EDITABLE | self::UPCOMING;
                        } elseif ($etime == 0) {                // Has starttime set, but no endtime -> never ending exam
                                $this->_state = self::EXAMINATABLE | self::RUNNING;
                        } elseif ($ctime < $etime) {            // After exam begin, but before its finished
                                $this->_state = self::EXAMINATABLE | self::RUNNING;
                        } elseif (!$this->_answered) {           // Unseen exam can be reused
                                $this->_state = self::REUSABLE | self::DELETABLE | self::FINISHED;
                        } elseif ($this->_corrected) {           // After exam has finished
                                $this->_state = self::CORRECTABLE | self::FINISHED | self::DECODABLE;
                        } else {
                                $this->_state = self::CORRECTABLE | self::FINISHED;
                        }

                        $stime = null;
                        $etime = null;
                        $ctime = null;
                }

                if ($this->_exam->published) {          // Contributable until published
                        $this->_state |= self::PUBLISHED;
                        $this->_state &= ~self::CONTRIBUTABLE;
                } else {
                        $this->_state |= self::CONTRIBUTABLE | self::DELETABLE;
                }

                if ($this->_answered == false) {        // Resuable until first seen
                        $this->_state |= self::EXAMINATABLE | self::EDITABLE | self::REUSABLE;
                        $this->_state &= ~self::CORRECTABLE;
                } else {
                        $this->_state |= self::ANSWERED;
                        $this->_state &= ~self::DELETABLE;
                }

                if ($this->_exam->testcase) {
                        $this->_state |= self::TESTCASE | self::DELETABLE;
                }
                if ($this->_exam->lockdown->enable) {
                        $this->_state |= self::LOCKDOWN;
                }

                if ($this->_state & self::FINISHED) {
                        $this->_state &= ~self::CONTRIBUTABLE;
                }
        }

        /**
         * Get number of uncorrected answers.
         * @return int 
         */
        private function getUncorrected()
        {
                if (!($connection = $this->_exam->getReadConnection())) {
                        throw new DatabaseException("Failed get read connection");
                }

                // 
                // This query will become slow on a heavy loaded server. Use 
                // result cache if possible.
                // 
                if (($resultset = $connection->query("
        SELECT  a.id
        FROM    questions q, students s, answers a
                LEFT JOIN results r ON 
                (a.id = r.answer_id AND r.correction NOT IN ('waiting','partial'))
        WHERE   s.exam_id = :examid AND
                s.id = a.student_id AND
                q.id = a.question_id AND 
                q.status != 'removed' AND 
                a.answered = 'Y' AND
                r.id IS NULL", array('examid' => $this->_exam->id)))) {
                        return $resultset->numRows();
                } else {
                        throw new DatabaseException("Failed query uncorrected answers.");
                }
        }

        /**
         * Get number of answers.
         * @return int
         */
        private function getAnswered()
        {
                if (!($connection = $this->_exam->getReadConnection())) {
                        throw new DatabaseException("Failed get read connection");
                }

                // 
                // This query will become slow on a heavy loaded server. Use 
                // result cache if possible.
                // 
                if (($resultset = $connection->query("
        SELECT  a.id
        FROM    questions q, students s, answers a
                LEFT JOIN results r ON a.id = r.answer_id
        WHERE   s.exam_id = :examid AND
                s.id = a.student_id AND
                q.id = a.question_id AND 
                q.status != 'removed' AND 
                a.answered = 'Y'", array('examid' => $this->_exam->id)))) {
                        return $resultset->numRows();
                } else {
                        throw new DatabaseException("Failed query answers on exam.");
                }
        }

        private function createCacheKey()
        {
                return sprintf("state-exam-%d", $this->_exam->id);
        }

}
