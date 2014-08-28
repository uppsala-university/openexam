<?php

// 
// The source code is copyrighted, with equal shared rights, between the
// authors (see the file AUTHORS) and the OpenExam project, Uppsala University 
// unless otherwise explicit stated elsewhere.
// 
// File:    Room.php
// Created: 2014-08-28 05:10:50
// 
// Author:  Anders Lövgren (QNET/BMC CompDept)
// 

namespace OpenExam\Models\Access;

/**
 * Access restricted Room model.
 *
 * @author Anders Lövgren (QNET/BMC CompDept)
 */
class Room extends \OpenExam\Models\Room implements AuthorizationInterface
{

        use \OpenExam\Models\Access\Traits\AuthorizationTrait;
}
