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
 * @author Anders Lövgren (Computing Department at BMC, Uppsala University)
 */
class LoggingCallback extends FileAdapter
{

        protected $_options;

        /**
         * Constructor.
         * @param string $name Name of the log file.
         * @param \Phalcon\Config|array $options The configuration options.
         */
        public function __construct($name, $options = array(
                'truncate' => false,
                'rotate'   => true,
                'compress' => true,
                'maxsize'  => 0,
                'maxage'   => 0
        ))
        {
                if (is_array($options)) {
                        $this->_options = (object) $options;
                } else {
                        $this->_options = (object) $options->toArray();
                }

                $this->_options->logfile = $name;
                $this->_options->process = false;

                if ($this->detect()) {
                        $this->apply();
                }
                parent::__construct($name);
        }

        public function __invoke()
        {
                if (func_num_args() == 1) {
                        $message = trim(func_get_arg(0));
                } else {
                        $message = trim(implode(PHP_EOL, func_get_args()));
                }
                if (strlen($message) != 0) {
                        parent::info(PHP_EOL . $message);
                }
        }

        /**
         * Detect if logfile should be rotated or truncated.
         * @return boolean
         */
        private function detect()
        {
                if (!file_exists($this->_options->logfile)) {
                        return false;
                }
                if (!($stat = stat($this->_options->logfile))) {
                        return false;
                }

                if ($this->_options->maxsize != 0) {
                        if ($this->_options->maxsize < $stat['size']) {
                                $this->_options->process = true;
                        }
                }
                if ($this->_options->maxage != 0) {
                        if ((time() - $this->_options->maxage) > $stat['mtime']) {
                                $this->_options->process = true;
                        }
                }

                return $this->_options->process;
        }

        /**
         * Apply any requested operations.
         */
        private function apply()
        {
                if (!($stat = stat($this->_options->logfile))) {
                        return false;
                }

                if ($this->_options->truncate) {
                        unlink($this->_options->logfile);
                } elseif ($this->_options->rotate) {
                        $this->_options->rotdate = date('Ymd', $stat['mtime']);
                        $this->_options->rotfile = sprintf("%s_%s", $this->_options->logfile, $this->_options->rotdate);
                        $this->rotate();
                }
                if ($this->_options->compress) {
                        $this->compress();
                }
        }

        /**
         * Rotate log.
         */
        private function rotate()
        {
                for ($i = 1;; $i++) {
                        $filename = sprintf("%s.%d", $this->_options->rotfile, $i);
                        $compfile = sprintf("%s.gz", $filename);
                        if (!file_exists($filename) && !file_exists($compfile)) {
                                break;
                        }
                }
                if (rename($this->_options->logfile, $filename)) {
                        $this->_options->rotfile = $filename;
                        $this->_options->compfile = $compfile;
                }
        }

        /**
         * Compress log.
         * @return boolean
         */
        private function compress()
        {
                if (!isset($this->_options->compfile)) {
                        return false;
                }

                $compfile = sprintf("compress.zlib://%s", $this->_options->compfile);
                if (!($handle = fopen($compfile, "w"))) {
                        return false;
                }

                fwrite($handle, file_get_contents($this->_options->rotfile));
                fclose($handle);
                unlink($this->_options->rotfile);
                return true;
        }

}
