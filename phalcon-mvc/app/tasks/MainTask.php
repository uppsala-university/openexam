<?php

// 
// The source code is copyrighted, with equal shared rights, between the
// authors (see the file AUTHORS) and the OpenExam project, Uppsala University 
// unless otherwise explicit stated elsewhere.
// 
// File:    MainTask.php
// Created: 2014-09-08 20:41:13
// 
// Author:  Anders Lövgren (Computing Department at BMC, Uppsala University)
// 

namespace OpenExam\Console\Tasks;

/**
 * The main task.
 *
 * @author Anders Lövgren (Computing Department at BMC, Uppsala University)
 */
class MainTask extends \Phalcon\CLI\Task
{

        public function indexAction()
        {
                printf(__METHOD__ . PHP_EOL);
        }

}
