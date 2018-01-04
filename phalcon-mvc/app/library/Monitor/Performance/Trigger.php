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
// File:    Trigger.php
// Created: 2016-06-09 03:54:14
// 
// Author:  Anders Lövgren (QNET/BMC CompDept)
// 

namespace OpenExam\Library\Monitor\Performance;

use OpenExam\Models\Performance;

/**
 * The triggers interface.
 * @author Anders Lövgren (QNET/BMC CompDept)
 */
interface Trigger
{
        /**
         * How to format UNIX timestamps.
         */
        const DATE_FORMAT = '%Y-%m-%d %H:%M:%S';

        /**
         * Process this performance model.
         * @param Performance $model
         */
        function process($model);
}
