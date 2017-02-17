<?php

// 
// The source code is copyrighted, with equal shared rights, between the
// authors (see the file AUTHORS) and the OpenExam project, Uppsala University 
// unless otherwise explicit stated elsewhere.
// 
// File:    Check.php
// Created: 2017-02-15 16:48:10
// 
// Author:  Anders Lövgren (Computing Department at BMC, Uppsala University)
// 

namespace OpenExam\Library\Core\Exam;

use OpenExam\Models\Access;
use OpenExam\Models\Exam;
use OpenExam\Models\Question;
use OpenExam\Models\Student;

/**
 * Check exam for readiness.
 * 
 * <code>
 * $check = new Check($exam);
 * $check->isReady();                   // Check if exam is ready.
 * $status = $check->getStatus();       // Get exam readiness status.
 * </code>
 * 
 * @author Anders Lövgren (Computing Department at BMC, Uppsala University)
 */
class Check
{

        /**
         * Exam is not ready for use.
         */
        const STATUS_NOT_READY = 1;
        /**
         * Exam need attention (i.e. questions are missing).
         */
        const STATUS_NEED_ATTENTION = 2;
        /**
         * All status checks has passed.
         */
        const STATUS_IS_READY = 3;
        /**
         * Exam has no security.
         */
        const SECURITY_NONE = 0;
        /**
         * Computer lockdown is enabled.
         */
        const SECURITY_LOCKDOWN = 1;
        /**
         * Lockdown locations are present.
         */
        const SECURITY_LOCATION = 2;
        /**
         * All security features are enabled.
         */
        const SECURITY_FULL = 3;
        /**
         * All required tasks completed.
         */
        const TASK_ALL_COMPLETED = 0;
        /**
         * Add questions is remaining.
         */
        const TASK_ADD_QUESTIONS = 1;
        /**
         * Add students is remaining.
         */
        const TASK_ADD_STUDENTS = 2;
        /**
         * Set start time is remaining.
         */
        const TASK_SET_STARTTIME = 3;
        /**
         * Set exam name is remaining.
         */
        const TASK_SET_NAME = 4;
        /**
         * Set exam security is remaining.
         */
        const TASK_SET_SECURITY = 5;
        /**
         * Publish exam is remaining.
         */
        const TASK_PUBLISH_EXAM = 6;

        /**
         * The exam model.
         * @var Exam 
         */
        private $_exam;

        /**
         * Constructor.
         * @param Exam $exam The exam model.
         */
        public function __construct($exam)
        {
                $this->_exam = $exam;
        }

        /**
         * Destructor.
         */
        public function __destruct()
        {
                unset($this->_exam);
        }

        /**
         * Check if exam is published.
         * @return boolean
         */
        public function isPublished()
        {
                return $this->_exam->published;
        }

        /**
         * Check that exam has name.
         * @return bool
         */
        public function hasName()
        {
                return isset($this->_exam->name) && strlen($this->_exam->name) != 0;
        }

        /**
         * Check if exam has questions.
         * @return boolean
         */
        public function hasQuestions()
        {
                return $this->getQuestionsCount() > 0;
        }

        /**
         * Check if exam has full security.
         * @return boolean
         */
        public function hasSecurity()
        {
                if (!$this->_exam->lockdown->enable) {
                        return false;
                } elseif ($this->getAccessCount() == 0) {
                        return false;
                } else {
                        return true;
                }
        }

        /**
         * Get security level.
         * 
         * Returns a bitmask of SECURITY_XXX constants. The return value is
         * SECURITY_NONE if security is missing or SECURITY_FULL if all 
         * security features are enabled.
         * 
         * <code>
         * if (($features = $check->getSecurity()) != Check::SECURITY_FULL) {
         *      if (!($features & Check::SECURITY_LOCKDOWN)) {
         *              // Lockdown not enabled
         *      }
         *      if (!($features & Check::SECURITY_LOCATION)) {
         *              // Location is missing
         *      }
         * }
         * </code>
         * 
         * @return int
         */
        public function getSecurity()
        {
                $level = self::SECURITY_NONE;

                if ($this->_exam->lockdown->enable) {
                        $level |= self::SECURITY_LOCKDOWN;
                }
                if ($this->getAccessCount() > 0) {
                        $level |= self::SECURITY_LOCATION;
                }

                return $level;
        }

        /**
         * Check if exam has starttime.
         * @return boolean
         */
        public function hasStartTime()
        {
                return isset($this->_exam->starttime);
        }

        /**
         * Check if exam has students.
         * @return boolean
         */
        public function hasStudents()
        {
                return $this->getStudentsCount() > 0;
        }

        /**
         * Get exam status.
         * 
         * Returns one of the STATUS_XXX constants.
         * @return int
         */
        public function getStatus()
        {
                if (!$this->isPublished()) {
                        return self::STATUS_NOT_READY;
                }
                if (!$this->hasName()) {
                        return self::STATUS_NOT_READY;
                }
                if (!$this->hasStartTime()) {
                        return self::STATUS_NOT_READY;
                }
                if (!$this->hasStudents()) {
                        return self::STATUS_NOT_READY;
                }
                if (!$this->hasQuestions()) {
                        return self::STATUS_NOT_READY;
                }
                if (!$this->hasSecurity()) {
                        return self::STATUS_NEED_ATTENTION;
                } else {
                        return self::STATUS_IS_READY;
                }
        }

        /**
         * Check if exam is ready for use.
         * 
         * This method will check status of all features and return true
         * only if all tests passes.
         * 
         * @return boolean
         */
        public function isReady()
        {
                return $this->getStatus() == self::STATUS_IS_READY;
        }

        /**
         * Get remaining task to complete.
         * @return int
         */
        public function getRemainingTask()
        {
                if ($this->isReady()) {
                        return self::TASK_ALL_COMPLETED;
                } elseif (!$this->hasQuestions()) {
                        return self::TASK_ADD_QUESTIONS;
                } elseif (!$this->hasStudents()) {
                        return self::TASK_ADD_STUDENTS;
                } elseif (!$this->hasStartTime()) {
                        return self::TASK_SET_STARTTIME;
                } elseif (!$this->hasName()) {
                        return self::TASK_SET_NAME;
                } elseif (!$this->isPublished()) {
                        return self::TASK_PUBLISH_EXAM;
                } elseif (!$this->hasSecurity()) {
                        return self::TASK_SET_SECURITY;
                }
        }

        /**
         * Get number of active questions.
         * @return int
         */
        private function getQuestionsCount()
        {
                return Question::count(array(
                            'conditions' => "exam_id = :exam: AND status = 'active'",
                            'bind'       => array(
                                    'exam' => $this->_exam->id
                            )
                ));
        }

        /**
         * Get number of access lists (exam locations).
         * @return int
         */
        private function getAccessCount()
        {
                return Access::count(array(
                            'conditions' => "exam_id = :exam:",
                            'bind'       => array(
                                    'exam' => $this->_exam->id
                            )
                ));
        }

        /**
         * Get number of students.
         * @return int
         */
        private function getStudentsCount()
        {
                return Student::count(array(
                            'conditions' => "exam_id = :exam:",
                            'bind'       => array(
                                    'exam' => $this->_exam->id
                            )
                ));
        }

}
