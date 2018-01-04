<?php

/*
 * Copyright (C) 2014-2018 The OpenExam Project
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

        public function __construct($domain = null)
        {
                while (true) {
                        if (isset($domain)) {
                                $this->user = sprintf("%s@%s", self::generate(), $domain);
                        } else {
                                $this->user = self::generate();
                        }
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
                        if (\OpenExam\Models\Corrector::findFirstByUser($this->user)) {
                                continue;
                        }
                        if (\OpenExam\Models\Exam::findFirstByCreator($this->user)) {
                                continue;
                        }
                        break;
                }
        }

        private static function generate()
        {
                return sprintf("tuser%s%03d", substr(md5(time()), 0, 7), \rand(0, 999));
        }

        public function __toString()
        {
                return $this->user;
        }

}
