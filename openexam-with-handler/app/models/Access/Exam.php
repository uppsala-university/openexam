<?php

// 
// The source code is copyrighted, with equal shared rights, between the
// authors (see the file AUTHORS) and the OpenExam project, Uppsala University 
// unless otherwise explicit stated elsewhere.
// 
// File:    Exam.php
// Created: 2014-08-28 05:01:25
// 
// Author:  Anders Lövgren (QNET/BMC CompDept)
// 

namespace OpenExam\Models\Access;

use OpenExam\Models\Access\Traits\Exception;

/**
 * Access restricted Exam model.
 *
 * @author Anders Lövgren (QNET/BMC CompDept)
 */
class Exam extends \OpenExam\Models\Exam implements AuthorizationInterface
{

        use \OpenExam\Models\Access\Traits\AuthorizationTrait;

        private function checkRole()
        {
                if (($roles = self::getService('roles')) == false) {
                        throw new Exception(_("Roles service ('roles') is missing."));
                }
                if ($roles->aquire($this->rrole, $this->id) == false) {
                        throw new Exception(_("You are not authorized to access this exam."));
                }
        }

}
