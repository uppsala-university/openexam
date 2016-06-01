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
 * 
 * @author Anders Lövgren (QNET/BMC CompDept)
 */
interface Counter
{

        /**
         * Get counter type.
         * @return string
         */
        function getType();

        /**
         * Get counter name.
         * @return string
         */
        function getName();

        /**
         * Get counter title.
         * @return string
         */
        function getTitle();

        /**
         * Get counter description.
         * @return string
         */
        function getDescription();

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
         * Check if performance counter for sub type exists.
         * @param string $type The counter sub type.
         * @return boolean
         */
        function hasCounter($type);

        /**
         * Get sub type counter.
         * @param string $type The counter type.
         * @return Counter 
         */
        function getCounter($type);

        /**
         * Check if counter uses source field.
         * 
         * The returned value is an array containing all available source
         * names (from the source field). If counter don't support source,
         * then they should return null.
         * 
         * @return boolean
         */
        function hasSource();

        /**
         * Get all source names for this counter.
         * @return array
         */
        function getSources();
}
