<?php

// 
// The source code is copyrighted, with equal shared rights, between the
// authors (see the file AUTHORS) and the OpenExam project, Uppsala University 
// unless otherwise explicit stated elsewhere.
// 
// File:    Answer.php
// Created: 2017-10-29 21:00:59
// 
// Author:  Anders Lövgren (QNET)
// 

namespace OpenExam\Library\Core\Exam\State;

use OpenExam\Library\Database\Exception as DatabaseException;
use OpenExam\Models\Exam;
use Phalcon\Cache\BackendInterface;

/**
 * Answer state class.
 * 
 * This class is for internal use by the state class only. Used to cache the 
 * answered state on an exam.
 * 
 * @author Anders Lövgren (QNET)
 */
class Answer
{

        /**
         * The cache lifetime.
         */
        const CACHE_LIFETIME = 120;

        /**
         * The cache key.
         * @var string 
         */
        private $_ckey;
        /**
         * The number of answers.
         * @var int 
         */
        private $_count;
        /**
         * The exam model.
         * @var Exam 
         */
        private $_exam;

        /**
         * Constructor.
         * @param Exam $exam The exam model.
         * @param BackendInterface $cache The cache backend.
         */
        public function __construct($exam, $cache)
        {
                $this->_ckey = sprintf("state-exam-%d-answer", $exam->id);
                $this->_exam = $exam;

                if ($cache->exists($this->_ckey, self::CACHE_LIFETIME)) {
                        $this->_count = $cache->get($this->_ckey, self::CACHE_LIFETIME);
                } else {
                        $this->_count = $this->getAnswered();
                        $cache->save($this->_ckey, $this->_count, self::CACHE_LIFETIME);
                }
        }

        /**
         * Check if answer exist.
         * 
         * Return true if answer count for this exam is larger than zero.
         * @return bool
         */
        public function exist()
        {
                return $this->_count > 0;
        }

        /**
         * Get number of answers.
         * @return int
         */
        public function count()
        {
                return $this->_count;
        }

        /**
         * Reset answer count cache.
         * 
         * @param BackendInterface $cache The cache backend interface.
         * @return bool
         */
        public function reset($cache)
        {
                $this->_count = $this->getAnswered();
                return $cache->delete($this->_ckey);
        }

        /**
         * Get number of answers.
         * 
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
        SELECT  COUNT(a.id)
        FROM    questions q, students s, answers a
                LEFT JOIN results r ON a.id = r.answer_id
        WHERE   s.exam_id = :examid AND
                s.id = a.student_id AND
                q.id = a.question_id AND 
                q.status != 'removed' AND 
                a.answered = 'Y'", array('examid' => $this->_exam->id)))) {
                        return (int) current($resultset->fetch());
                } else {
                        throw new DatabaseException("Failed query answers on exam.");
                }
        }

}
