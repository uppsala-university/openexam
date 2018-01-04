<?php

/*
 * Copyright (C) 2015-2018 The OpenExam Project
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
// File:    Silent.php
// Created: 2015-03-16 05:39:55
// 
// Author:  Anders Lövgren (QNET/BMC CompDept)
// 

namespace OpenExam\Library\Security\Signup;

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
