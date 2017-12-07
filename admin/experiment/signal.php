<?php

// 
// The source code is copyrighted, with equal shared rights, between the
// authors (see the file AUTHORS) and the OpenExam project, Uppsala University 
// unless otherwise explicit stated elsewhere.
// 
// File:    signal.php
// Created: 2017-12-07 13:59:47
// 
// Author:  Anders Lövgren (Computing Department at BMC, Uppsala University)
// 

declare(ticks = 1); 

echo "pcntl_signal_dispatch()\n";
pcntl_signal_dispatch();

echo "pcntl_signal()\n";
pcntl_signal(SIGTERM, function ($signal) {
        echo "got signal $signal\n";
});

sleep(1);

echo "posix_kill()\n";
posix_kill(posix_getpid(), SIGTERM);
