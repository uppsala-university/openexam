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
        private $exam;
        
        /**
         * @var Student 
         */
        private $student;

        /**
         * Constructor.
         * @param Exam $exam The current exam.
         * @param Student $student The current student.
         */
        public function __construct($exam, $student)
        {
                $this->exam = $exam;
                $this->student = $student;
        }

        /**
         * 
         */
        public function prepare()
        {
                // TODO: Add code to initialize exam
        }

}
