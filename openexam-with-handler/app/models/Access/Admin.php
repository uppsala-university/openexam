<?php

// 
// The source code is copyrighted, with equal shared rights, between the
// authors (see the file AUTHORS) and the OpenExam project, Uppsala University 
// unless otherwise explicit stated elsewhere.
// 
// File:    Admin.php
// Created: 2014-08-27 19:33:44
// 
// Author:  Anders Lövgren (QNET/BMC CompDept)
// 

namespace OpenExam\Models\Access;

/**
 * Access restricted Admin model.
 *
 * @author Anders Lövgren (QNET/BMC CompDept)
 */
class Admin extends \OpenExam\Models\Admin implements AuthorizationInterface
{

        use \OpenExam\Models\Access\Traits\AuthorizationTrait,
            \OpenExam\Models\Access\Traits\SystemWideTrait;
}
