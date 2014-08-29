<?php

// 
// The source code is copyrighted, with equal shared rights, between the
// authors (see the file AUTHORS) and the OpenExam project, Uppsala University 
// unless otherwise explicit stated elsewhere.
// 
// File:    Teacher.php
// Created: 2014-08-28 04:48:16
// 
// Author:  Anders Lövgren (QNET/BMC CompDept)
// 

namespace OpenExam\Models\Access;

/**
 * Access restricted teacher model.
 *
 * @author Anders Lövgren (QNET/BMC CompDept)
 */
class Teacher extends \OpenExam\Models\Teacher implements AuthorizationInterface
{

        use \OpenExam\Models\Access\Traits\AuthorizationTrait,
            \OpenExam\Models\Access\Traits\SystemWideTrait;
}
