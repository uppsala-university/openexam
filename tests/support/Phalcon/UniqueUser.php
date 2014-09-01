<?php

// 
// The source code is copyrighted, with equal shared rights, between the
// authors (see the file AUTHORS) and the OpenExam project, Uppsala University 
// unless otherwise explicit stated elsewhere.
// 
// File:    RolesHelper.php
// Created: 2014-09-01 13:50:58
// 
// Author:  Anders Lövgren (Computing Department at BMC, Uppsala University)
// 

namespace OpenExam\Tests\Phalcon;

/**
 * Generate unique username.
 * 
 * This class generates a unique username not present in any model.
 *
 * @author Anders Lövgren (Computing Department at BMC, Uppsala University)
 */
class UniqueUser
{

        public $user;

        public function __construct()
        {
                while (true) {
                        $this->user = sprintf("tuser%s%03d", substr(md5(time()), 0, 7), \rand(0, 999));
                        if (\OpenExam\Models\Admin::findFirstByUser($this->user)) {
                                continue;
                        }
                        if (\OpenExam\Models\Teacher::findFirstByUser($this->user)) {
                                continue;
                        }
                        if (\OpenExam\Models\Student::findFirstByUser($this->user)) {
                                continue;
                        }
                        if (\OpenExam\Models\Contributor::findFirstByUser($this->user)) {
                                continue;
                        }
                        if (\OpenExam\Models\Invigilator::findFirstByUser($this->user)) {
                                continue;
                        }
                        if (\OpenExam\Models\Decoder::findFirstByUser($this->user)) {
                                continue;
                        }
                        if (\OpenExam\Models\Question::findFirstByUser($this->user)) {
                                continue;
                        }
                        if (\OpenExam\Models\Exam::findFirstByCreator($this->user)) {
                                continue;
                        }
                        break;
                }
        }

        public function __toString()
        {
                return $this->user;
        }

}
