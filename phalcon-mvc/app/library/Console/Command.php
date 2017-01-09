<?php

// 
// The source code is copyrighted, with equal shared rights, between the
// authors (see the file AUTHORS) and the OpenExam project, Uppsala University 
// unless otherwise explicit stated elsewhere.
// 
// File:    Command.php
// Created: 2016-05-22 21:30:36
// 
// Author:  Anders Lövgren (QNET/BMC CompDept)
// 

namespace OpenExam\Library\Console;

/**
 * Runs command in the console (shell).
 *
 * This is a simple command runner that executes a command and return 
 * whether it succeeded (status == 0) or not. The stderr is captures under
 * the assumption of being written to stderr (2). This is not always the
 * case, e.g. when executing a pipe. 
 * 
 * For more complex cases, e.g. when running process in background, use the
 * Process class,
 * 
 * @see Process
 * @author Anders Lövgren (QNET/BMC CompDept)
 */
class Command
{

        /**
         * The comamnd to run.
         * @var string 
         */
        private $_command;
        /**
         * Current working directory.
         * @var string 
         */
        private $_cwd;
        /**
         * Optional environment variables.
         * @var array 
         */
        private $_env;
        /**
         * The output string.
         * @var string 
         */
        private $_output;
        /**
         * The error message (if any).
         * @var string 
         */
        private $_error;
        /**
         * The exit status from command.
         * @var int 
         */
        private $_status = -1;
        /**
         * The error file path.
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
                $this->_errfile = sprintf("%s/command-%d.err", sys_get_temp_dir(), getmypid());
        }
        
        public function __destruct()
        {
                unset($this->_command);
                unset($this->_cwd);
                unset($this->_env);
                unset($this->_errfile);
                unset($this->_error);
                unset($this->_output);
        }

        /**
         * Set current working directory.
         * @param string $cwd The directory.
         */
        public function setDirectory($cwd)
        {
                $this->_cwd = $cwd;
        }

        /**
         * Set optional environment.
         * @param array $env The environment variables.
         */
        public function setEnvironment($env)
        {
                $this->_env = $env;
        }

        /**
         * Get command output (stdout).
         * 
         * @return array
         */
        public function getOutput()
        {
                return $this->_output;
        }

        /**
         * Get command error (stderr).
         * @return string
         */
        public function getError()
        {
                return $this->_error;
        }

        /**
         * Check if executed command has error output.
         * @return boolean
         */
        public function hasError()
        {
                return isset($this->_error);
        }

        /**
         * Get command exit code.
         * @return int
         */
        public function getStatus()
        {
                return $this->_status;
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
         * Return true if command has finished execute.
         * @return boolean
         */
        public function isFinished()
        {
                return $this->_status != -1;
        }

        /**
         * Set error output from file.
         */
        private function setError()
        {
                if (filesize($this->_errfile) > 0) {
                        $this->_error = file_get_contents($this->_errfile);
                }
                if (unlink($this->_errfile) == false) {
                        trigger_error("Failed unlink error file $this->_errfile");
                }
        }

        /**
         * Change working directory.
         * @throws \Exception
         */
        private function changeDirectory()
        {
                if (!file_exists($this->_cwd)) {
                        throw new \Exception("The working directory don't exist");
                }
                if (chdir($this->_cwd)) {
                        throw new \Exception("Failed change working directory");
                }
        }

        /**
         * Change environment variables.
         * @return array Array of replaced environment variables.
         */
        private function changeEnvironment()
        {
                $env = array();

                foreach ($this->_env as $key => $val) {
                        if (($tmp = getenv($key))) {
                                $env[$key] = $tmp;
                        }
                        putenv("$key=$val");
                }

                return $env;
        }

        /**
         * Restore environment variables.
         * @param array $env Previous replaced environment variables.
         */
        private function restoreEnvironment($env)
        {
                foreach ($this->_env as $key => $val) {
                        putenv($key);           // cleanup
                }
                foreach ($env as $key => $val) {
                        putenv("$key=$val");    // restore
                }
        }

        /**
         * Execute the command.
         * 
         * Returns true if successful (no errors) or false on error. Use 
         * getStatus() and hasError()/getError() to find out the cause.
         * 
         * @return boolean Return true if successful.
         * @throws \Exception
         */
        public function execute()
        {
                $cmd = sprintf("%s 2> %s", $this->_command, $this->_errfile);

                // 
                // Set current working directory:
                // 
                if (isset($this->_cwd)) {
                        $this->changeDirectory();
                }

                // 
                // Set environment variables.
                // 
                if (isset($this->_env)) {
                        $env = $this->changeEnvironment();
                }

                // 
                // Wipe output from previous exec.
                // 
                unset($this->_output);
                
                // 
                // Execute program.
                // 
                exec($cmd, $this->_output, $this->_status);

                // 
                // Check for errors:
                // 
                if (file_exists($this->_errfile)) {
                        $this->setError();
                }

                // 
                // Cleanup/restore environment variables:
                // 
                if (isset($this->_env)) {
                        $this->restoreEnvironment($env);
                }

                // 
                // Return true if successful.
                // 
                if ($this->_status != 0) {
                        return false;
                } elseif (isset($this->_error)) {
                        return false;
                } else {
                        return true;
                }
        }

}
