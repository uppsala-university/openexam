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
use Phalcon\Mvc\User\Component;
use OpenExam\Library\Database\Exception as DatabaseException;

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
         * Lifetime of cached answered and corrected state.
         */
        const CACHE_LIFETIME = 30;
        /**
         * Correction status cache key.
         */
        const CACHE_SUB_KEY_CORRECTED = 'corrected';
        /**
         * Answer status cache key.
         */
        const CACHE_SUB_KEY_ANSWERED = 'answered';

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
         * This exam has been corrected.
         * @var bool 
         */
        private $corrected;
        /**
         * This exam has at least one answer.
         * @var bool 
         */
        private $answered;

        /**
         * Constructor.
         * @param Exam $exam The examination object.
         */
        public function __construct($exam)
        {
                $this->exam = $exam;
                $this->refresh(false);
        }

        /**
         * Refresh examination state.
         */
        public function refresh($nocache = true)
        {
                $this->setAnswered($nocache);
                $this->setCorrected($nocache);
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
                        } elseif (!$this->answered) {           // Unseen exam can be reused
                                $this->state = self::REUSABLE | self::DELETABLE | self::FINISHED;
                        } elseif ($this->corrected) {           // After exam has finished
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

                if ($this->answered == false) {     // Contributable and resuable until first seen
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
         * Check correction status.
         * @return bool True if exam is fully corrected.
         */
        public function isCorrected()
        {
                return $this->corrected;
        }

        /**
         * Check answer status
         * @return bool True if examination has answers.
         */
        public function isAnswered()
        {
                return $this->answered;
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

        /**
         * Set exam corrected status.
         * @param bool $nocache Use cached status if false.
         */
        private function setCorrected($nocache)
        {
                if ($nocache) {
                        $this->corrected = ($this->getUncorrected() == 0);
                        return;
                }

                $cachekey = $this->createCacheKey(self::CACHE_SUB_KEY_CORRECTED);
                $lifetime = self::CACHE_LIFETIME;

                if ($this->cache->exists($cachekey, $lifetime)) {
                        $this->corrected = $this->cache->get($cachekey, $lifetime);
                } else {
                        $this->corrected = ($this->getUncorrected() == 0);
                        $this->cache->save($cachekey, $this->corrected, $lifetime);
                }
        }

        /**
         * Set exam answered status.
         * @param bool $nocache Use cached status if false.
         */
        private function setAnswered($nocache)
        {
                if ($nocache) {
                        $this->answered = ($this->getAnswered() != 0);
                        return;
                }

                $cachekey = $this->createCacheKey(self::CACHE_SUB_KEY_ANSWERED);
                $lifetime = self::CACHE_LIFETIME;

                if ($this->cache->exists($cachekey, $lifetime)) {
                        $this->answered = $this->cache->get($cachekey, $lifetime);
                } else {
                        $this->answered = ($this->getAnswered() != 0);
                        $this->cache->save($cachekey, $this->answered, $lifetime);
                }
        }

        private function createCacheKey($type)
        {
                return sprintf("state-exam-%d-%s", $this->exam->id, $type);
        }

        /**
         * Get number of uncorrected answers.
         * @return int 
         */
        private function getUncorrected()
        {
                if (!($connection = $this->exam->getReadConnection())) {
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
                r.id IS NULL", array('examid' => $this->exam->id)))) {
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
                if (!($connection = $this->exam->getReadConnection())) {
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
                a.answered = 'Y'", array('examid' => $this->exam->id)))) {
                        return $resultset->numRows();
                } else {
                        throw new DatabaseException("Failed query answers on exam.");
                }
        }

}
