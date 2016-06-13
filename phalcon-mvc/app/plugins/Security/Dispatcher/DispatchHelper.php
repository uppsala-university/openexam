<?php

// 
// The source code is copyrighted, with equal shared rights, between the
// authors (see the file AUTHORS) and the OpenExam project, Uppsala University 
// unless otherwise explicit stated elsewhere.
// 
// File:    DispatchHelper.php
// Created: 2015-02-17 10:38:55
// 
// Author:  Anders Lövgren (Computing Department at BMC, Uppsala University)
// 

namespace OpenExam\Plugins\Security\Dispatcher;

/**
 * Interface for dispatch helper classes.
 * @access private
 * @author Anders Lövgren (Computing Department at BMC, Uppsala University)
 */
interface DispatchHelper
{

        /**
         * Get handler data.
         * @return array 
         */
        function getData();
}
