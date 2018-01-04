<?php

/*
 * Copyright (C) 2014-2018 The OpenExam Project
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
 */

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
