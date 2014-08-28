<?php

// 
// The source code is copyrighted, with equal shared rights, between the
// authors (see the file AUTHORS) and the OpenExam project, Uppsala University 
// unless otherwise explicit stated elsewhere.
// 
// File:    Invigilator.php
// Created: 2014-08-28 04:55:31
// 
// Author:  Anders Lövgren (QNET/BMC CompDept)
// 

namespace OpenExam\Models\Access;

/**
 * Access restricted Invigilator model.
 *
 * @author Anders Lövgren (QNET/BMC CompDept)
 */
class Invigilator extends \OpenExam\Models\Invigilator implements AuthorizationInterface
{

        use \OpenExam\Models\Access\Traits\AuthorizationTrait,
            \OpenExam\Models\Access\Traits\ExamRelationTrait;
}
