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
// File:    Process.php
// Created: 2016-05-23 13:08:38
// 
// Author:  Anders Lövgren (QNET/BMC CompDept)
// 

namespace OpenExam\Library\Console;

use Exception;

/**
 * Interactive process runner.
 * 
 * Example usage:
 * <code>
 * try {
 *      // 
 *      // Start vmstat command:
 *      // 
 *      $process = new Process("vmstat -S M 5");
 *      $process->open();
 * 
 *      // 
 *      // Read command output five times:
 *      // 
 *      for ($i = 0; $i < 5; ++$i) {
 *              if ($process->wait()) {
 *                      printf("stat: %s\n", $process->read());
 *              }
 *      }
 * 
 *      // 
 *      // Ask to close polite, then terminate (nuke):
 *      // 
 *      if (!$process->close()) {
 *              if ($process->terminate()) {
 *                      $process->terminate(SIGKILL);   // sledge
 *              }
 *      }
 * } catch(\Exception $exception) {
 *      die($exception->getMessage());
 * }
 * </code>
 *
 * @see Command
 * @author Anders Lövgren (QNET/BMC CompDept)
 */
class Process
{

        /**
         * Wait for output failed.
         */
        const WAIT_FAILED = -1;
        /**
         * Wait for output timed out.
         */
        const WAIT_TIMEOUT = 0;
        /**
         * Wait succeeded. Ready for read.
         */
        const WAIT_READY = 1;

        /**
         * The command string.
         * @var string 
         */
        private $_command;
        /**
         * The command handle.
         * @var resource 
         */
        private $_handle;
        /**
         * The command pipes.
         * @var array 
         */
        private $_pipes = array();
        /**
         * Current working directory.
         * @var string
         */
        private $_cwd;
        /**
         * Environment variables.
         * @var array 
         */
        private $_env;
        /**
         * The exit code.
         * @var int 
         */
        private $_code = -1;
        /**
         * The error output file.
         * @var string 
         */
        private $_errfile;

        /**
         * Constructor.
         * @param string $command The command to run.
         */
        public function __construct($command)
        {
                $this->_command = $command;
                $this->_errfile = sprintf("%s/process-%d.err", sys_get_temp_dir(), getmypid());
        }

        /**
         * Destructor.
         * 
         * Cleans up error file and close handle.
         */
        public function __destruct()
        {
                if (is_resource($this->_handle)) {
                        $this->close();
                }
                if (file_exists($this->_errfile)) {
                        unlink($this->_errfile);
                }

                unset($this->_command);
                unset($this->_cwd);
                unset($this->_env);
                unset($this->_errfile);
                unset($this->_pipes);
        }

        /**
         * Set current working directory (optional).
         * @param string $cwd The directory path.
         */
        public function setDirectory($cwd)
        {
                $this->_cwd = $cwd;
        }

        /**
         * Set environment variables (optional).
         * @param array $env The environment variables.
         */
        public function setEnvironment($env)
        {
                $this->_env = $env;
        }

        /**
         * Set blocking mode on input/output stream.
         * @param boolean $read Set blocking mode on read.
         * @param boolean $write Set blocking mode on write.
         */
        public function setBlocking($read = true, $write = true)
        {
                if (!stream_set_blocking($this->_pipes[1], $read)) {
                        throw new Exception("Failed set blocking mode $read on read stream");
                }
                if (!stream_set_blocking($this->_pipes[0], $write)) {
                        throw new Exception("Failed set blocking mode $read on write stream");
                }
        }

        /**
         * Get command string.
         * @return string
         */
        public function getCommand()
        {
                return $this->_command;
        }

        /**
         * Get command handle.
         * @return resource
         */
        public function getHandle()
        {
                return $this->_handle;
        }

        /**
         * Get exit code.
         * @return int
         */
        public function getExitCode()
        {
                return $this->_code;
        }

        /**
         * Get process status.
         * @return array
         * @throws Exception
         */
        public function getStatus($key = null)
        {
                if (!($status = proc_get_status($this->_handle))) {
                        throw new Exception("Failed get process status");
                } elseif (!isset($key)) {
                        return $status;
                } elseif (isset($status[$key])) {
                        return $status[$key];
                } else {
                        throw new \Excpetion("Process status key don't exist");
                }
        }

        /**
         * Check if process is running.
         * 
         * Returns true if the process is still running, false if it has 
         * been terminated. 
         * 
         * @return boolean
         */
        public function isRunning()
        {
                return $this->getStatus('running');
        }

        /**
         * Check if process is stopped.
         * 
         * Returns true if the child process has been stopped by a signal.
         * 
         * @return boolean
         */
        public function isStopped()
        {
                return $this->getStatus('stopped');
        }

        /**
         * Check process signaled status.
         * 
         * Return true if the child process has been terminated by an 
         * uncaught signal.
         * 
         * @return boolean
         */
        public function isSignaled()
        {
                return $this->getStatus('signaled');
        }

        /**
         * Check if process got error.
         * @return boolean
         */
        public function hasError()
        {
                return file_exists($this->_errfile) && filesize($this->_errfile) > 0;
        }

        /**
         * Get process errors.
         * @return string
         */
        public function getError()
        {
                return file_get_contents($this->_errfile);
        }

        /**
         * Write to program input.
         * @param string $str The message.
         * @return boolean
         */
        public function write($str)
        {
                return fwrite($this->_pipes[0], $str) > 0;
        }

        /**
         * Read from program output.
         * @return string|boolean
         */
        public function read()
        {
                return fgets($this->_pipes[1]);
        }

        /**
         * Return number of bytes readable on input stream before blocking.
         * @return int
         */
        public function available()
        {
                return stream_get_meta_data($this->_pipes[1])['unread_bytes'];
        }

        /**
         * Block calling thread waiting for events.
         * 
         * The result is either one of the WAIT_XXX constants. The WAIT_FAILED
         * status might happen if process was interupted by a signal.
         * 
         * @return int The wait for read status.
         */
        public function wait($timeout = null)
        {
                $read = array($this->_pipes[1]);
                $write = array();
                $except = null;

                if (($num = stream_select($read, $write, $except, $timeout)) === false) {
                        return self::WAIT_FAILED;
                } elseif ($num == 0) {
                        return self::WAIT_TIMEOUT;
                } else {
                        return self::WAIT_READY;
                }
        }

        /**
         * Open program (execute).
         * @throws Exception
         */
        public function open()
        {
                $descr = array(
                        0 => array("pipe", "r"),
                        1 => array("pipe", "w"),
                        2 => array("file", $this->_errfile, "w")
                );

                if (!($this->_handle = proc_open(
                    $this->_command, $descr, $this->_pipes, $this->_cwd, $this->_env
                    ))) {
                        throw new Exception("Failed open process");
                }
        }

        /**
         * Close program.
         * @return boolean True if successful closed.
         * @throws Exception
         */
        public function close()
        {
                if (($this->_code = proc_close($this->_handle)) < 0) {
                        throw new Exception("Failed close process");
                } else {
                        return $this->_code == 0;
                }
        }

        /**
         * Terminate process.
         * 
         * Send signal to process. The default signal is SIGTERM. Use SIGKILL
         * to force terminate by sending a signal that can't be trapped.
         * 
         * @param int $signal The signal to send.
         * @return boolean
         */
        public function terminate($signal = SIGTERM)
        {
                return proc_terminate($this->_handle, $signal);
        }

}
