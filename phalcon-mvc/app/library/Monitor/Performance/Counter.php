<?php

// 
// The source code is copyrighted, with equal shared rights, between the
// authors (see the file AUTHORS) and the OpenExam project, Uppsala University 
// unless otherwise explicit stated elsewhere.
// 
// File:    Counter.php
// Created: 2016-05-24 02:16:19
// 
// Author:  Anders Lövgren (QNET/BMC CompDept)
// 

namespace OpenExam\Library\Monitor\Performance;

/**
 * Interface for performance counters.
 * @author Anders Lövgren (QNET/BMC CompDept)
 */
interface Counter
{
        /**
         * Set performance counter data.
         * @param array $data The performance data.
         */
        function setData($data);
        
        /**
         * Get performance data.
         * @return array
         */
        function getData();
        
        /**
         * Get performance counter keys.
         * @return array
         */
        function getKeys();
        
        /**
         * Check if performance caounter exists.
         * @param string $type The counter type.
         * @return boolean
         */
        function hasCounter($type);
        
        /**
         * Get sub counter data.
         * @param string $type The counter type.
         * @return array 
         */
        function getCounter($type);
}
