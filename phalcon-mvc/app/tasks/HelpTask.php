<?php

// 
// The source code is copyrighted, with equal shared rights, between the
// authors (see the file AUTHORS) and the OpenExam project, Uppsala University 
// unless otherwise explicit stated elsewhere.
// 
// File:    HelpTask.php
// Created: 2014-09-09 05:44:47
// 
// Author:  Anders Lövgren (QNET/BMC CompDept)
// 

namespace OpenExam\Console\Tasks;

/**
 * Description of HelpTask
 *
 * @author Anders Lövgren (QNET/BMC CompDept)
 */
class HelpTask extends MainTask
{

        /**
         * Display usage information.
         */
        public function indexAction()
        {
                printf("Usage: --database [options...]\n");
        }

}
