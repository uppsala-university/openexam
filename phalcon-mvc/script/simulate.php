<?php

/*
 * Copyright (C) 2016-2018 The OpenExam Project
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
// File:    simulate.php
// Created: 2016-01-13 15:23:18
// 
// Author:  Anders Lövgren (Computing Department at BMC, Uppsala University)
// 

define('TASKS_PHP', __DIR__ . '/../app/config/system/tasks.php');
include(TASKS_PHP);

use OpenExam\Library\Console\Application;

try {
        $console = new Application($di);
        $console->process(array('task' => 'simulate'));
} catch (\Exception $exception) {
        $di->get('flash')->error($exception->getMessage());
        exit(255);
}
