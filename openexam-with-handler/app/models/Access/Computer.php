<?php

// 
// The source code is copyrighted, with equal shared rights, between the
// authors (see the file AUTHORS) and the OpenExam project, Uppsala University 
// unless otherwise explicit stated elsewhere.
// 
// File:    Computer.php
// Created: 2014-08-28 05:13:09
// 
// Author:  Anders Lövgren (QNET/BMC CompDept)
// 

namespace OpenExam\Models\Access;

/**
 * Access restricted Computer model.
 *
 * @author Anders Lövgren (QNET/BMC CompDept)
 */
class Computer extends \OpenExam\Models\Computer implements AuthorizationInterface
{

        use \OpenExam\Models\Access\Traits\AuthorizationTrait;
}
