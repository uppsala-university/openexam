<?php

// 
// The source code is copyrighted, with equal shared rights, between the
// authors (see the file AUTHORS) and the OpenExam project, Uppsala University 
// unless otherwise explicit stated elsewhere.
// 
// File:    DirectoryService.php
// Created: 2014-10-22 03:35:53
// 
// Author:  Anders Lövgren (QNET/BMC CompDept)
// 

namespace OpenExam\Library\Catalog;

/**
 * Interface for directory services.
 * @author Anders Lövgren (QNET/BMC CompDept)
 */
interface DirectoryService
{

        /**
         * Get groups for user.
         * @param string $principal The user principal name.
         * @return array
         */
        function getGroups($principal);

        /**
         * Get members of group.
         * @param string $group The group name.
         * @return array
         */
        function getMembers($group, $domain = null);

        /**
         * Get email addresses for user.
         * @param string $principal The user principal name.
         * @return array
         */
        function getEmail($principal);

}
