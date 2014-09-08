<?php

// 
// The source code is copyrighted, with equal shared rights, between the
// authors (see the file AUTHORS) and the OpenExam project, Uppsala University 
// unless otherwise explicit stated elsewhere.
// 
// File:    DatabaseTask.php
// Created: 2014-09-08 19:22:32
// 
// Author:  Anders Lövgren (Computing Department at BMC, Uppsala University)
// 

namespace OpenExam\Console\Tasks;

/**
 * Database task.
 *
 * @author Anders Lövgren (Computing Department at BMC, Uppsala University)
 */
class DatabaseTask extends \Phalcon\CLI\Task
{

        public function indexAction()
        {
                printf(__METHOD__ . PHP_EOL);
        }

        public function migrateAction()
        {
                printf(__METHOD__ . PHP_EOL);
        }

}
