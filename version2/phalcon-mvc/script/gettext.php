<?php

// 
// The source code is copyrighted, with equal shared rights, between the
// authors (see the file AUTHORS) and the OpenExam project, Uppsala University 
// unless otherwise explicit stated elsewhere.
// 
// File:    gettext.php
// Created: 2014-09-19 06:12:25
// 
// Author:  Anders Lövgren (QNET/BMC CompDept)
// 


define('TASKS_PHP', __DIR__ . '/../app/config/system/tasks.php');
include(TASKS_PHP);

use OpenExam\Library\Console\Application;

try {
        $console = new Application($di);
        $console->process(array('task' => 'gettext'));
} catch (\Exception $exception) {
        $di->flash->error($exception->getMessage());
        exit(255);
}
