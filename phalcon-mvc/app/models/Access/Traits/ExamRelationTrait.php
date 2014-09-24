<?php

// 
// The source code is copyrighted, with equal shared rights, between the
// authors (see the file AUTHORS) and the OpenExam project, Uppsala University 
// unless otherwise explicit stated elsewhere.
// 
// File:    ExamRelationTrait.php
// Created: 2014-08-28 04:45:08
// 
// Author:  Anders Lövgren (QNET/BMC CompDept)
// 

namespace OpenExam\Models\Access\Traits;

/**
 * Exam related trait on model.
 *
 * @author Anders Lövgren (QNET/BMC CompDept)
 */
trait ExamRelationTrait
{

        private function checkRole()
        {
                if ($this->_roles->aquire($this->_role, $this->exam_id) == false) {
                        throw new Exception(_("You are not authorized to access this exam."));
                }
        }

}
