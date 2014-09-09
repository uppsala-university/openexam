<?php

// 
// The source code is copyrighted, with equal shared rights, between the
// authors (see the file AUTHORS) and the OpenExam project, Uppsala University 
// unless otherwise explicit stated elsewhere.
// 
// File:    cli.php
// Created: 2014-09-08 16:30:53
// 
// Author:  Anders Lövgren (Computing Department at BMC, Uppsala University)
// 

define('TASKS_PHP', __DIR__ . '/../app/config/system/tasks.php');
include(TASKS_PHP);

use OpenExam\Library\Console\Application;

try {
        $console = new Application($di);
        $console->process();
} catch (\Phalcon\Exception $exception) {
        echo $exception->getMessage();
        exit(255);
}
