<?php

// 
// The source code is copyrighted, with equal shared rights, between the
// authors (see the file AUTHORS) and the OpenExam project, Uppsala University 
// unless otherwise explicit stated elsewhere.
// 
// File:    ManagerSearch.php
// Created: 2017-04-11 23:04:00
// 
// Author:  Anders Lövgren (QNET/BMC CompDept)
// 

namespace OpenExam\Library\Catalog\Search;

/**
 * The directory search interface.
 * @author Anders Lövgren (QNET/BMC CompDept)
 */
interface ManagerSearch
{

        /**
         * Get search result from directory manager.
         * @param DirectoryManager $manager The directory manager.
         * @return array
         */
        function getResult($manager);
}
