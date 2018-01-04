<?php

/*
 * Copyright (C) 2016-2018 The OpenExam Project
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
// File:    Formatter.php
// Created: 2016-01-21 10:03:26
// 
// Author:  Anders Lövgren (Computing Department at BMC, Uppsala University)
// 

namespace OpenExam\Library\Core;

use Phalcon\DI\Injectable as PhalconDI;
use Phalcon\DI\InjectionAwareInterface;
use Phalcon\DiInterface;
use Phalcon\Logger\Formatter\Line as LineFormatter;

/**
 * Log file formatter.
 * 
 * This class decorates each log message with remote address and logged
 * on user (principal name). The actual formatting is handled by parent
 * class.
 * 
 * In addition to using setFormat(), setMessageFormat() can be used to 
 * customize how the actual message text is formatted:
 * <code>
 * $formatter = new Formatter();
 * $formatter->setMessageFormat('%3$s (%1$s on %2$s)'); // message ... (user on host)
 * </code>
 *
 * @author Anders Lövgren (Computing Department at BMC, Uppsala University)
 */
class Formatter extends LineFormatter implements InjectionAwareInterface
{

        /**
         * Phalcon dependency injector.
         * @var PhalconDI 
         */
        private $_di;
        /**
         * The message formatting.
         * @var string 
         */
        private $_msgfmt = "- [%1\$s][%2\$s]:\n%3\$s\n";
        /**
         * Logged on user (principal name).
         * @var string 
         */
        private $_user = false;
        /**
         * Remote host name.
         * @var string 
         */
        private $_host = false;

        /**
         * Set message format. 
         * 
         * The order of arguments are: user, host and message itself. Use 
         * ordinal placeholders to re-order output.
         * 
         * @param string $format The format string.
         */
        public function setMessageFormat($format)
        {
                $this->_msgfmt = $format;
        }

        /**
         * Get message format.
         * @return string
         */
        public function getMessageFormat()
        {
                return $this->_msgfmt;
        }

        public function format($message, $type, $timestamp, $context = null)
        {
                if (isset($this->_di)) {
                        if (!$this->_user) {
                                $this->_user = $this->_di->get('user')->getPrincipalName();
                        }
                        if (!$this->_host) {
                                $this->_host = $this->_di->get('request')->getClientAddress(true);
                        }
                }

                $message = sprintf($this->_msgfmt, $this->_user, $this->_host, $message);
                return parent::format($message, $type, $timestamp, $context);
        }

        public function getDI()
        {
                return $this->_di;
        }

        public function setDI(DiInterface $dependencyInjector)
        {
                $this->_di = $dependencyInjector;
        }

}
