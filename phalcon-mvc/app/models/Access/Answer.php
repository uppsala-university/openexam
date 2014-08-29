<?php

// 
// The source code is copyrighted, with equal shared rights, between the
// authors (see the file AUTHORS) and the OpenExam project, Uppsala University 
// unless otherwise explicit stated elsewhere.
// 
// File:    Answer.php
// Created: 2014-08-28 05:15:11
// 
// Author:  Anders Lövgren (QNET/BMC CompDept)
// 

namespace OpenExam\Models\Access;

use OpenExam\Library\Security\Roles;
use OpenExam\Models\Access\Traits\Exception;

/**
 * Access restricted Answer model.
 *
 * @author Anders Lövgren (QNET/BMC CompDept)
 */
class Answer extends \OpenExam\Models\Answer implements AuthorizationInterface
{

        use \OpenExam\Models\Access\Traits\AuthorizationTrait;

        private function checkRole()
        {
                if (($roles = self::getService('roles')) == false) {
                        throw new Exception(_("Roles service ('roles') is missing."));
                }
                if ($roles->aquire($this->rrole, $this->question->exam_id) == false) {
                        throw new Exception(_("You are not authorized to access this answer."));
                }
        }

        private function checkObject()
        {
                $user = self::getUser();

                if (($this->rrole == Roles::student)) {
                        if ($this->student->user != $user) {
                                throw new Exception(_("You are not the owner of this answer."));
                        }
                }
        }

}
