<?php

// 
// The source code is copyrighted, with equal shared rights, between the
// authors (see the file AUTHORS) and the OpenExam project, Uppsala University 
// unless otherwise explicit stated elsewhere.
// 
// File:    Collector.php
// Created: 2016-05-23 22:59:57
// 
// Author:  Anders Lövgren (QNET/BMC CompDept)
// 

namespace OpenExam\Library\Monitor\Performance;

/**
 * Interface for performance collectors.
 * 
 * @author Anders Lövgren (QNET/BMC CompDept)
 */
interface Collector
{

        /**
         * Start performance collector.
         */
        function start();

        /**
         * Stop performance collector.
         */
        function stop();
}
