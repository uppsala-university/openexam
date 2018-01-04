<?php

/*
 * Copyright (C) 2017-2018 The OpenExam Project
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
 */

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
