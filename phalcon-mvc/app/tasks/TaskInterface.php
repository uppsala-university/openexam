<?php

// 
// The source code is copyrighted, with equal shared rights, between the
// authors (see the file AUTHORS) and the OpenExam project, Uppsala University 
// unless otherwise explicit stated elsewhere.
// 
// File:    TaskInterface.php
// Created: 2016-01-13 13:52:13
// 
// Author:  Anders Lövgren (Computing Department at BMC, Uppsala University)
// 

namespace OpenExam\Console\Tasks;

/**
 * Common interface for tasks.
 * @author Anders Lövgren (Computing Department at BMC, Uppsala University)
 */
interface TaskInterface
{

        /**
         * Get task usage information.
         * @return array
         */
        static function getUsage();

        /**
         * Display usage information.
         */
        public function helpAction();
}
