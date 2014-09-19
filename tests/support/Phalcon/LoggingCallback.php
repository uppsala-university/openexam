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

        private $options;

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
                        $this->options = (object) $options;
                } else {
                        $this->options = (object) $options->toArray();
                }

                $this->options->logfile = $name;
                $this->options->process = false;

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
                if (!file_exists($this->options->logfile)) {
                        return false;
                }
                if (!($stat = stat($this->options->logfile))) {
                        return false;
                }

                if ($this->options->maxsize != 0) {
                        if ($this->options->maxsize < $stat['size']) {
                                $this->options->process = true;
                        }
                }
                if ($this->options->maxage != 0) {
                        if ((time() - $this->options->maxage) > $stat['mtime']) {
                                $this->options->process = true;
                        }
                }

                return $this->options->process;
        }

        /**
         * Apply any requested operations.
         */
        private function apply()
        {
                if ($this->options->truncate) {
                        unlink($this->options->logfile);
                } elseif ($this->options->rotate) {
                        $this->options->rotdate = date('Ymd', $stat['mtime']);
                        $this->options->rotfile = sprintf("%s_%s", $this->options->logfile, $this->options->rotdate);
                        $this->rotate();
                }
                if ($this->options->compress) {
                        $this->compress();
                }
        }

        /**
         * Rotate log.
         */
        private function rotate()
        {
                for ($i = 1;; $i++) {
                        $filename = sprintf("%s.%d", $this->options->rotfile, $i);
                        $compfile = sprintf("%s.gz", $filename);
                        if (!file_exists($filename) && !file_exists($compfile)) {
                                break;
                        }
                }
                if (rename($this->options->logfile, $filename)) {
                        $this->options->rotfile = $filename;
                        $this->options->compfile = $compfile;
                }
        }

        /**
         * Compress log.
         * @return boolean
         */
        private function compress()
        {
                if (!isset($this->options->compfile)) {
                        return false;
                }

                $compfile = sprintf("compress.zlib://%s", $this->options->compfile);
                if (!($handle = fopen($compfile, "w"))) {
                        return false;
                }

                fwrite($handle, file_get_contents($this->options->rotfile));
                fclose($handle);
                unlink($this->options->rotfile);
                return true;
        }

}
