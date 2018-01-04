<?php

/*
 * Copyright (C) 2017-2018 The OpenExam Project
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
 */

// 
// File:    Result.php
// Created: 2017-10-29 21:00:47
// 
// Author:  Anders Lövgren (QNET)
// 

namespace OpenExam\Library\Core\Exam\State;

use OpenExam\Library\Database\Exception as DatabaseException;
use OpenExam\Models\Exam;
use Phalcon\Cache\BackendInterface;

/**
 * Result state class.
 * 
 * This class is for internal use by the state class only. Used to cache the 
 * correction state on an exam.
 * 
 * @author Anders Lövgren (QNET)
 */
class Result
{

        /**
         * The cache lifetime.
         */
        const CACHE_LIFETIME = 10;

        /**
         * The cache key.
         * @var string 
         */
        private $_ckey;
        /**
         * The number of uncorrected results.
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
                $this->_ckey = sprintf("state-exam-%d-result", $exam->id);
                $this->_exam = $exam;

                if ($cache->exists($this->_ckey, self::CACHE_LIFETIME)) {
                        $this->_count = $cache->get($this->_ckey, self::CACHE_LIFETIME);
                } else {
                        $this->_count = $this->getUncorrected();
                        $cache->save($this->_ckey, $this->_count, self::CACHE_LIFETIME);
                }
        }

        /**
         * Check if uncorrected results exist.
         * 
         * Return true if number of uncorrected results for this exam is 
         * larger than zero.
         * @return bool
         */
        public function exist()
        {
                return $this->_count > 0;
        }

        /**
         * Get number of uncorrected results.
         * @return int
         */
        public function count()
        {
                return $this->_count;
        }

        /**
         * Reset uncorrected results count cache.
         * 
         * @param BackendInterface $cache The cache backend interface.
         * @return bool
         */
        public function reset($cache)
        {
                $this->_count = $this->getUncorrected();
                return $cache->delete($this->_ckey);
        }

        /**
         * Get number of uncorrected answers.
         * 
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
        SELECT  COUNT(a.id)
        FROM    questions q, students s, answers a
                LEFT JOIN results r ON 
                (a.id = r.answer_id AND r.correction NOT IN ('waiting','partial'))
        WHERE   s.exam_id = :examid AND
                s.id = a.student_id AND
                q.id = a.question_id AND 
                q.status != 'removed' AND 
                a.answered = 'Y' AND
                r.id IS NULL", array('examid' => $this->_exam->id)))) {
                        return (int) current($resultset->fetch());
                } else {
                        throw new DatabaseException("Failed query uncorrected answers.");
                }
        }

}
