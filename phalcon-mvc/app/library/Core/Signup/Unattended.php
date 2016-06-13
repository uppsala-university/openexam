<?php

// 
// The source code is copyrighted, with equal shared rights, between the
// authors (see the file AUTHORS) and the OpenExam project, Uppsala University 
// unless otherwise explicit stated elsewhere.
// 
// File:    Silent.php
// Created: 2015-03-16 05:39:55
// 
// Author:  Anders Lövgren (QNET/BMC CompDept)
// 

namespace OpenExam\Library\Core\Signup;

use Phalcon\Mvc\User\Component;

/**
 * Unattended user signup.
 *
 * @author Anders Lövgren (QNET/BMC CompDept)
 */
class Unattended extends Component
{

        /**
         * Process unattended user signup.
         * 
         * If caller is employee: Assign teacher exams and grant teacher
         * role to caller.
         * 
         * If caller is student: Assing student exams.
         */
        public function process()
        {
                if ($this->user->affiliation->isEmployee()) {
                        $teacher = new Teacher();
                        $teacher->insert();
                        if ($teacher->isEnabled() && $teacher->isApplied() == false) {
                                foreach ($teacher->getExams() as $index) {
                                        $teacher->assign($index);
                                }
                        }
                }

                if ($this->user->affiliation->isStudent()) {
                        $student = new Student();
                        if ($student->isEnabled() && $student->isApplied() == false) {
                                foreach ($student->getExams() as $index) {
                                        $student->assign($index);
                                }
                        }
                }
        }

}
