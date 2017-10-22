<?php

// 
// The source code is copyrighted, with equal shared rights, between the
// authors (see the file AUTHORS) and the OpenExam project, Uppsala University 
// unless otherwise explicit stated elsewhere.
// 
// File:    Flash.php
// Created: 2014-09-11 01:51:40
// 
// Author:  Anders Lövgren (QNET/BMC CompDept)
// 

namespace OpenExam\Library\Console;

use Phalcon\Flash\Direct;

/**
 * Custom flash message service for console.
 *
 * @author Anders Lövgren (QNET/BMC CompDept)
 */
class Flash extends Direct
{

        public function __construct()
        {
                parent::__construct();
                $this->setAutomaticHtml(false);
        }

        /**
         * Output message to console.
         * 
         * If automaticHtml is set, then the output is delegated to parent
         * class. The type argument defines different message prefixes:
         * <code>
         * $types = array(
         *      'error'   => '(-)',
         *      'warning' => '(!)',
         *      'notice'  => '(i)',
         *      'success' => '(+)'
         * );
         * </code>
         * 
         * @param string $type The message type (e.g. 'error').
         * @param string $message The message to output.
         */
        public function outputMessage($type, $message)
        {
                if ($this->_automaticHtml) {
                        parent::outputMessage($type, $message);
                } elseif ($type == "error") {
                        fprintf(STDERR, "(-) %s\n", $message);
                } elseif ($type == "warning") {
                        printf("(!) %s\n", $message);
                } elseif ($type == "notice") {
                        printf("(i) %s\n", $message);
                } elseif ($type == "success") {
                        printf("(+) %s\n", $message);
                } elseif (isset($type)) {
                        printf("%s: %s\n", $type, $message);
                } else {
                        printf("%s\n", $message);
                }
        }

        /**
         * Write message without prefix.
         */
        public function write($message = "")
        {
                $this->outputMessage(null, $message);
        }

}
