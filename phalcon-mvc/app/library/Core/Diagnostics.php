<?php

// 
// The source code is copyrighted, with equal shared rights, between the
// authors (see the file AUTHORS) and the OpenExam project, Uppsala University 
// unless otherwise explicit stated elsewhere.
// 
// File:    Diagnostics.php
// Created: 2016-04-19 00:32:33
// 
// Author:  Anders Lövgren (QNET/BMC CompDept)
// 

namespace OpenExam\Library\Core;

use OpenExam\Library\Monitor\Performance;
use Phalcon\Mvc\User\Component;

/**
 * System diagnistics.
 * 
 * Methods requrning performance counters and check service status.
 *
 * @author Anders Lövgren (QNET/BMC CompDept)
 */
class Diagnostics extends Component
{

        /**
         * Get system performance.
         * @return Performance 
         */
        public function getPerformanceStatus()
        {
                $performance = new Performance();
                return $performance;
        }

        /**
         * Get database status.
         */
        public function getDatabaseStatus()
        {
                
        }

        /**
         * Get authentication service status.
         */
        public function getAuthenticatorStatus()
        {
                
        }

        /**
         * Get web process status.
         */
        public function getProcessStatus()
        {
                
        }

        /**
         * Get catalog service status.
         */
        public function getCatalogStatus()
        {
                
        }

}
