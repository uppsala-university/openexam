<?php

// 
// The source code is copyrighted, with equal shared rights, between the
// authors (see the file AUTHORS) and the OpenExam project, Uppsala University 
// unless otherwise explicit stated elsewhere.
// 
// File:    Formatter.php
// Created: 2016-01-21 10:03:26
// 
// Author:  Anders Lövgren (Computing Department at BMC, Uppsala University)
// 

namespace OpenExam\Library\Core;

use Phalcon\DI\Injectable as PhalconDI;
use Phalcon\DI\InjectionAwareInterface;
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

        public function format($message, $type, $timestamp, $context)
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

        public function setDI($dependencyInjector)
        {
                $this->_di = $dependencyInjector;
        }

}
