<?php

// 
// The source code is copyrighted, with equal shared rights, between the
// authors (see the file AUTHORS) and the OpenExam project, Uppsala University 
// unless otherwise explicit stated elsewhere.
// 
// File:    LoggingCallback.php
// Created: 2014-09-17 00:52:14
// 
// Author:  Anders Lövgren (QNET/BMC CompDept)
// 

namespace OpenExam\Tests\Phalcon;

use Phalcon\Logger\Adapter\File as FileAdapter;

/**
 * Log output from unit tests.
 * 
 * This class provides the PHP equivalent to C++ functional objects. It's
 * an alternative to closure and callback functions, with the importance
 * that it permits state to be preserved between invokations.
 * 
 * <code>
 * 
 * // Usage: inside a unit test:
 * 
 * $callback = new LoggingCallback("/tmp/phpunit-output.log")
 * $this->setOutputCallback($callback);
 * 
 * </code>
 * 
 * @author Anders Lövgren (QNET/BMC CompDept)
 */
class LoggingCallback extends FileAdapter
{

        public function __invoke()
        {
                if (func_num_args() == 1) {
                        $message = trim(func_get_arg(0));
                } else {
                        $message = trim(implode("\n", func_get_args()));
                }
                if (strlen($message) != 0) {
                        parent::info("\n" . $message);
                }
        }

}
