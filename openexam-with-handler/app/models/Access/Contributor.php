<?php

// 
// The source code is copyrighted, with equal shared rights, between the
// authors (see the file AUTHORS) and the OpenExam project, Uppsala University 
// unless otherwise explicit stated elsewhere.
// 
// File:    Contributor.php
// Created: 2014-08-28 04:50:45
// 
// Author:  Anders Lövgren (QNET/BMC CompDept)
// 

namespace OpenExam\Models\Access;

/**
 * Access restricted Contributor model.
 *
 * @author Anders Lövgren (QNET/BMC CompDept)
 */
class Contributor extends \OpenExam\Models\Contributor implements AuthorizationInterface
{

        use \OpenExam\Models\Access\Traits\AuthorizationTrait,
            \OpenExam\Models\Access\Traits\ExamRelationTrait;
}
