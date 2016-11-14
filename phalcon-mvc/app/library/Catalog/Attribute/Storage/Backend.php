<?php

// 
// The source code is copyrighted, with equal shared rights, between the
// authors (see the file AUTHORS) and the OpenExam project, Uppsala University 
// unless otherwise explicit stated elsewhere.
// 
// File:    Backend.php
// Created: 2016-11-13 23:42:16
// 
// Author:  Anders Lövgren (QNET/BMC CompDept)
// 

namespace OpenExam\Library\Catalog\Attribute\Storage;

use OpenExam\Models\User;

/**
 * The storage backend interface.
 * @author Anders Lövgren (QNET/BMC CompDept)
 */
interface Backend
{

        /**
         * Check if user exist.
         * @param string $principal The user principal name.
         * @return boolean 
         */
        function exists($principal);

        /**
         * Insert user attributes.
         * @param User $user The user model.
         */
        function insert($user);

        /**
         * Delete user.
         * @param string $principal The user principal name.
         */
        function delete($principal);
}
