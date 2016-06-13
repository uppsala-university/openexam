<?php

// 
// The source code is copyrighted, with equal shared rights, between the
// authors (see the file AUTHORS) and the OpenExam project, Uppsala University 
// unless otherwise explicit stated elsewhere.
// 
// File:    Setup.php
// Created: 2014-09-19 12:11:58
// 
// Author:  Anders Lövgren (Computing Department at BMC, Uppsala University)
// 

namespace OpenExam\Library\Globalization\Translate;

/**
 * Translation setup.
 * Interface for classes managing translation resources.
 * 
 * @author Anders Lövgren (Computing Department at BMC, Uppsala University)
 */
interface Setup
{

        /**
         * Initialize.
         */
        function initialize();

        /**
         * Cleanup.
         */
        function clean();

        /**
         * Update translations.
         */
        function update();

        /**
         * Compile translations.
         */
        function compile();

        /**
         * Merge translations.
         */
        function merge();
}
