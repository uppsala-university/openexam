<?php

// 
// The source code is copyrighted, with equal shared rights, between the
// authors (see the file AUTHORS) and the OpenExam project, Uppsala University 
// unless otherwise explicit stated elsewhere.
// 
// File:    Grade.php
// Created: 2017-12-05 11:14:03
// 
// Author:  Anders Lövgren (Computing Department at BMC, Uppsala University)
// 

namespace OpenExam\Library\Core\Exam;

use OpenExam\Models\Exam;
use RuntimeException;

/**
 * Handle exam grades.
 * 
 * The exam can either be represent by percent or score. This class provides
 * translation of score to percent based grades that is used internal in the
 * system.
 *
 * @author Anders Lövgren (Computing Department at BMC, Uppsala University)
 */
class Grade
{

        /**
         * Grades uses percent.
         */
        const HAS_PERCENT = 1;
        /**
         * Grades uses points.
         */
        const HAS_POINTS = 2;

        /**
         * The exam model.
         * @var Exam 
         */
        private $_exam;
        /**
         * The grades representation.
         * @var array 
         */
        private $_data = array();
        /**
         * Exam grades in scores.
         * @var array 
         */
        private $_scores = array();
        /**
         * Exam grades in percent.
         * @var array 
         */
        private $_grades = array();
        /**
         * The total score.
         * @var float 
         */
        private $_maximum = 0;
        /**
         * One of the HAS_XXX constants.
         * @var int 
         */
        private $_mode;

        /**
         * Constructor.
         * @param Exam $exam The exam model.
         */
        public function __construct($exam)
        {
                $this->_exam = $exam;
                $this->_mode = self::HAS_PERCENT;

                $this->setRepresentation();
                $this->setMaximum();
                $this->setGrades();
                $this->setScores();
        }

        /**
         * Check if exam has grades.
         * @return bool
         */
        public function hasGrades()
        {
                return count($this->_grades) != 0;
        }

        /**
         * Check if exam has scores.
         * @return bool
         */
        public function hasScores()
        {
                return count($this->_scores) != 0;
        }

        /**
         * Check if exam grades is using scores.
         * @return bool
         */
        public function useScores()
        {
                return $this->_mode == self::HAS_POINTS;
        }

        /**
         * Get exam grades based on scores.
         * @return array
         */
        public function getScores()
        {
                return $this->_scores;
        }

        /**
         * Get exam grades based on percent.
         * @return array
         */
        public function getGrades()
        {
                return $this->_grades;
        }

        /**
         * Get maximum score.
         * @return float
         */
        public function getMaximum()
        {
                return $this->_maximum;
        }

        /**
         * Get grade for score.
         * @param float $score The student score.
         */
        public function getGrade($score, $normalize = true)
        {
                $highest = key($this->_grades);

                if ($normalize) {
                        $score = $this->getNormalized($score);
                }

                foreach ($this->_grades as $grade => $limit) {
                        if ($score >= $limit) {
                                $highest = $grade;
                        }
                }

                return $highest;
        }

        /**
         * Check if grade is highest.
         * 
         * @param string $grade The current grade.
         * @return bool
         */
        public function isHighest($grade)
        {
                return $this->getNextGrade($grade) == $grade;
        }

        /**
         * Check if grade is lowest.
         * 
         * @param string $grade The current grade.
         * @return bool
         */
        public function isLowest($grade)
        {
                return key($this->_grades) == $grade;
        }

        /**
         * Check if student is close to pass next grade.
         * 
         * @param float $score The student score.
         * @param int $threadhold The threshold in percent.
         * @return bool
         */
        public function isClose($score, $threadhold = 5)
        {
                if ($score == 0) {
                        return false;
                }

                $score = $this->getNormalized($score);
                $grade = $this->getGrade($score, false);
                $limit = $this->getNextLimit($grade);

                if ($score > $limit) {
                        return false;   // Already highest grade.
                }

                return $score + $threadhold >= $limit;
        }

        /**
         * Get score missing for next grade.
         * 
         * @param float $score The student score.
         * @return float
         */
        public function getMissing($score)
        {
                $grade = $this->getGrade($score);
                $limit = $this->getNextLimit($grade);
                $needs = $this->getPoints($limit);

                return $needs - $score;
        }

        /**
         * Get normalized score.
         * 
         * Return score normalized to percent of maximum score on this exam.
         * 
         * @param float $score The student score.
         * @return float
         */
        public function getNormalized($score)
        {
                if ($this->_maximum == 0) {
                        return 0;
                } else {
                        return (100) * $score / $this->_maximum;
                }
        }

        /**
         * Get limit as points.
         * 
         * Convert and return limit in points relative to maximum score on
         * this exam.
         * 
         * @param float $limit The score in percent.
         * @return float
         */
        public function getPoints($limit)
        {
                return $this->_maximum * ($limit / 100);
        }

        /**
         * Get next grade.
         * 
         * @param string $grade The current grade.
         * @return string 
         */
        public function getNextGrade($grade)
        {
                $prev = $grade;

                foreach (array_reverse(array_keys($this->_grades)) as $curr) {
                        if ($curr == $grade) {
                                return $prev;
                        } else {
                                $prev = $curr;
                        }
                }

                return false;   // Non-existing.
        }

        /**
         * Get next limit.
         * 
         * @param string $grade The current grade.
         * @return float 
         */
        public function getNextLimit($grade)
        {
                $next = $this->getNextGrade($grade);
                return $this->_grades[$next];
        }

        /**
         * Set exam grades in scores (points).
         */
        private function setScores()
        {
                if ($this->_mode == self::HAS_POINTS) {
                        $this->_scores = $this->_data;
                        return;
                }
                if ($this->_maximum == 0) {
                        return;
                }

                foreach ($this->_data as $key => $val) {
                        $this->_scores[$key] = $this->_maximum * ($val / 100);
                }
        }

        /**
         * Set exam grades in percent.
         */
        private function setGrades()
        {
                if ($this->_mode == self::HAS_PERCENT) {
                        $this->_grades = $this->_data;
                        return;
                }

                if ($this->_maximum == 0) {
                        return;
                }

                foreach ($this->_data as $key => $val) {
                        $this->_grades[$key] = 100 * ($val / $this->_maximum);
                }
        }

        /**
         * Set maximum score.
         */
        private function setMaximum()
        {
                foreach ($this->_exam->questions as $question) {
                        $this->_maximum += $question->score;
                }
        }

        /**
         * Set internal representation.
         * @throws RuntimeException
         */
        private function setRepresentation()
        {
                $match = array();

                foreach (preg_split("/[\r\n]/", $this->_exam->grades) as $grade) {
                        list($key, $val) = explode(":", $grade);
                        if (preg_match("/([\d.]+)\s*(.*)/", $val, $match)) {
                                $this->_data[$key] = $match[1];
                        }
                }

                if (count($match) == 3 && strlen($match[2]) != 0) {
                        switch ($match[2]) {
                                case 'p':
                                case 'points':
                                        $this->_mode = self::HAS_POINTS;
                                        break;
                                case '%':
                                case 'percent':
                                        $this->_mode = self::HAS_PERCENT;
                                        break;
                                default:
                                        throw new RuntimeException(sprintf("Unexpected score suffix %s in exam grades", $match[2]));
                        }
                }
        }

}
