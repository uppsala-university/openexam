<?php

// 
// The source code is copyrighted, with equal shared rights, between the
// authors (see the file AUTHORS) and the OpenExam project, Uppsala University 
// unless otherwise explicit stated elsewhere.
// 
// File:    Topic.php
// Created: 2014-08-28 04:57:34
// 
// Author:  Anders Lövgren (QNET/BMC CompDept)
// 

namespace OpenExam\Models\Access;

/**
 * Access restricted Topic model.
 *
 * @author Anders Lövgren (QNET/BMC CompDept)
 */
class Topic extends \OpenExam\Models\Topic implements AuthorizationInterface
{

        use \OpenExam\Models\Access\Traits\AuthorizationTrait,
            \OpenExam\Models\Access\Traits\ExamRelationTrait;
}
