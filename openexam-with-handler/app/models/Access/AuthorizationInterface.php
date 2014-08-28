<?php

// 
// The source code is copyrighted, with equal shared rights, between the
// authors (see the file AUTHORS) and the OpenExam project, Uppsala University 
// unless otherwise explicit stated elsewhere.
// 
// File:    AuthorizeInterface.php
// Created: 2014-08-27 22:39:55
// 
// Author:  Anders Lövgren (QNET/BMC CompDept)
// 

namespace OpenExam\Models\Access;

/**
 * Checked model access interface.
 * @author Anders Lövgren (QNET/BMC CompDept)
 */
interface AuthorizationInterface
{

        const create = "create";
        const read = "read";
        const update = "update";
        const delete = "delete";

        function setRole($role);

        function hasRole();

        function getRole();
}
