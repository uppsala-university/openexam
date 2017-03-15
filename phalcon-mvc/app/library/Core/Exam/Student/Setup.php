<?php

// 
// The source code is copyrighted, with equal shared rights, between the
// authors (see the file AUTHORS) and the OpenExam project, Uppsala University 
// unless otherwise explicit stated elsewhere.
// 
// File:    Setup.php
// Created: 2014-12-17 02:51:48
// 
// Author:  Anders Lövgren (QNET/BMC CompDept)
// 

namespace OpenExam\Library\Core\Exam\Student;

use Phalcon\Mvc\User\Component;

/**
 * Prepare exam for student on first access.
 * 
 * @author Anders Lövgren (QNET/BMC CompDept)
 */
class Setup extends Component
{

        /**
         * @var Exam 
         */
        private $_exam;
        /**
         * @var Student 
         */
        private $_student;

        /**
         * Constructor.
         * @param Exam $exam The current exam.
         * @param Student $student The current student.
         */
        public function __construct($exam, $student)
        {
                $this->_exam = $exam;
                $this->_student = $student;
        }

        /**
         * Destructor.
         */
        public function __destruct()
        {
                unset($this->_exam);
                unset($this->_student);
        }

        /**
         * Prepare exam for first use.
         */
        public function prepare()
        {
                // TODO: Add code to initialize exam
        }

}
