<?php

// 
// The source code is copyrighted, with equal shared rights, between the
// authors (see the file AUTHORS) and the OpenExam project, Uppsala University 
// unless otherwise explicit stated elsewhere.
// 
// File:    database.php
// Created: 2014-09-09 03:27:04
// 
// Author:  Anders Lövgren (QNET/BMC CompDept)
// 

define('TASKS_PHP', __DIR__ . '/../app/config/system/tasks.php');
include(TASKS_PHP);

use OpenExam\Library\Console\Application;

try {
        $console = new Application($di);
        $console->process(array('task' => 'unittest'));
} catch (\Exception $exception) {
        $di->get('flash')->error($exception->getMessage());
        exit(255);
}
