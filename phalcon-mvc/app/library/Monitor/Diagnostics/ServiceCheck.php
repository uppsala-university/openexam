<?php

// 
// The source code is copyrighted, with equal shared rights, between the
// authors (see the file AUTHORS) and the OpenExam project, Uppsala University 
// unless otherwise explicit stated elsewhere.
// 
// File:    ServiceCheck.php
// Created: 2016-06-02 02:43:42
// 
// Author:  Anders Lövgren (QNET/BMC CompDept)
// 

namespace OpenExam\Library\Monitor\Diagnostics;

/**
 * Interface for service checks.
 * @author Anders Lövgren (QNET/BMC CompDept)
 */
interface ServiceCheck
{

        /**
         * True if last check has failed.
         * @boolean
         */
        function hasFailed();

        /**
         * Check if service is online.
         * @return boolean
         */
        function isOnline();

        /**
         * Check if service is working.
         * @return boolean
         */
        function isWorking();

        /**
         * Get check result.
         * @return array
         */
        function getResult();
}
