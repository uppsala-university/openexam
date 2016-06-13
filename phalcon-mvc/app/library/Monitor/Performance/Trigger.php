<?php

// 
// The source code is copyrighted, with equal shared rights, between the
// authors (see the file AUTHORS) and the OpenExam project, Uppsala University 
// unless otherwise explicit stated elsewhere.
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
