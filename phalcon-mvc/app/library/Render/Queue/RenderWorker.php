<?php

/*
 * Copyright (C) 2017-2018 The OpenExam Project
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
// File:    RenderWorker.php
// Created: 2017-12-07 01:04:39
// 
// Author:  Anders Lövgren (QNET)
// 

namespace OpenExam\Library\Render\Queue;

use ErrorException;
use Exception;
use OpenExam\Library\Render\Renderer;
use OpenExam\Models\Render;

/**
 * The render worker.
 * 
 * This class is part of the infrastructure that is consuming jobs from the 
 * render queue. By utilizing a producer/consumer model for rendering the load
 * on each server should be within bounds as only a fixed number of workers
 * are running at any time consuming render jobs one after another until the
 * render queue is empty.
 * 
 * In fact, the worker model should be more efficient that simply accepting jobs
 * direct from AJAX as many servers can cooperate on providing a render service
 * while a AJAX request for multiple render jobs are typical bound to a single
 * server that becomes overloaded.
 * 
 * The process running this class can use signals:
 * 
 *   1. SIGTERM: Ask process to exit ordered.
 *   2. SIGHUP:  Start polling for queued jobs.
 * 
 * The default is to sleep for a fixed number of seconds (30 unless defined) 
 * after consuming all queued render jobs. To infinite block running process 
 * waiting for a signal, pass the BLOCK_INFINITE constant as sleep value. Don't 
 * use LOG_XXX when started as a daemon process.
 * 
 * @author Anders Lövgren (QNET)
 */
class RenderWorker
{

        /**
         * Log to standard output.
         */
        const LOG_STDOUT = "php://stdout";
        /**
         * Log to standard error.
         */
        const LOG_STDERR = "php://stderr";
        /**
         * The default poll interval.
         */
        const BLOCK_INTERVAL = 5;
        /**
         * The block infinite sleep value.
         */
        const BLOCK_INFINITE = -1;

        /**
         * The render service.
         * @var Renderer 
         */
        private $_service;
        /**
         * Write processing to log file.
         * @var string 
         */
        private $_logfile;
        /**
         * The poll interval.
         * @var int 
         */
        private $_sleep;
        /**
         * The process exit flag.
         * @var bool 
         */
        private $_done = false;

        /**
         * Constructor.
         * @param Renderer $service The render service.
         */
        public function __construct($service, $logfile = null, $sleep = self::BLOCK_INTERVAL)
        {
                $this->_service = $service;
                $this->_logfile = $logfile;

                $this->_sleep = $sleep;
        }

        /**
         * Set log file.
         * @param string $path The logfile path.
         */
        public function setLogfile($path)
        {
                $this->_logfile = $path;
        }

        /**
         * Set poll interval.
         * @param int $sleep The sleep interval.
         */
        public function setInterval($sleep)
        {
                $this->_sleep = $sleep;
        }

        /**
         * Write PID to file.
         * @param string $path The PID file destination.
         */
        public function writePid($path)
        {
                file_put_contents($path, getmypid());
        }

        /**
         * Process render jobs.
         */
        public function process()
        {
                declare(ticks = 1);

                $this->setupSignalHandler();
                $this->setupErrorHandler();

                $this->log("Starting");

                while (!$this->_done) {
                        $this->fetch();
                }

                $this->log("Finished");
        }

        /**
         * Fetch single job.
         */
        private function fetch()
        {
                try {
                        if (!$this->consume()) {
                                $this->sleep();
                        }
                } catch (Exception $exception) {
                        $this->log(sprintf("Failed fetch job (%s)", $exception->getMessage()));
                }
        }

        /**
         * Consume next job.
         * 
         * Return true if job was rendered or false if render queue is empty.
         * @return boolean
         */
        private function consume()
        {
                $consumer = new RenderConsumer();

                if (!$consumer->hasNext()) {
                        unset($consumer);
                        return false;
                }

                $job = $consumer->getNext();
                $this->render($job);
                $consumer->setResult($job);

                return true;
        }

        /**
         * Render single job.
         * @param Render $job The render model.
         */
        private function render($job)
        {
                if (!$this->check($job)) {
                        $job->message = "Pre-condition failed (check details in worker log).";
                        return;
                }

                try {
                        $this->log(sprintf("Rendering job %d", $job->id));

                        $job->stime = time();
                        $this->_service->save($job->file, array(array('page' => $job->url)));
                        $job->etime = time();

                        $job->message = sprintf("Render time %d seconds", $job->etime - $job->stime);
                        $job->status = Render::STATUS_FINISH;
                } catch (Exception $exception) {
                        $this->log(sprintf("Failed render job %d (%s)", $job->id, $exception->getMessage()));
                        $job->message = $exception->getMessage();
                        $job->status = Render::STATUS_FAILED;
                }
        }

        /**
         * Check common problems.
         * 
         * @param Render $job The render model.
         * @return boolean
         */
        private function check($job)
        {
                if (!isset($job->file)) {
                        $this->log(sprintf("Missing file property in job %d", $job->id));
                        return false;
                } else {
                        $dest = dirname($job->file);
                }

                if (!file_exists($dest)) {
                        $this->log("The target directory $dest is missing");
                        return false;
                }
                if (!is_writable($dest)) {
                        $this->log("The target directory $dest is not writable");
                        return false;
                }

                return true;
        }

        /**
         * Go to sleep.
         */
        private function sleep()
        {
                $this->log("Sleeping: No queued jobs");

                if ($this->_sleep == self::BLOCK_INFINITE) {
                        pcntl_sigwaitinfo(array(SIGTERM, SIGHUP));
                } else {
                        sleep($this->_sleep);
                }

                $this->log("Wakeup: Polling for queued jobs...");
        }

        /**
         * Write log message.
         * @param string $message The log message.
         */
        private function log($message)
        {
                if (empty($this->_logfile)) {
                        return;
                }

                if (!file_put_contents($this->_logfile, self::format($message), FILE_APPEND)) {
                        trigger_error("Failed write message");
                }
        }

        /**
         * Format log message.
         * 
         * @param string $message The log message.
         * @return string
         */
        private static function format($message)
        {
                return sprintf("%s %s\n", strftime("%x %X"), $message);
        }

        /**
         * Setup signal handling.
         */
        private function setupSignalHandler()
        {
                // 
                // Check if required extension is loaded. If not, override the
                // block infinite value if required.
                // 
                if (!extension_loaded("pcntl")) {
                        if ($this->_sleep == self::BLOCK_INFINITE) {
                                $this->_sleep = self::BLOCK_INTERVAL;
                        }
                        $this->log("The pcntl extension is missing. No signal handler installed.");
                        return;
                }

                // 
                // Install signal handler for SIGTERM:
                // 
                if (pcntl_signal(SIGTERM, function ($signal) {
                            $this->log("Got terminate signal $signal. Setting exit flag for loop.");
                            $this->_done = true;
                    })) {
                        $this->log("Installed signal handler (SIGTERM)");
                } else {
                        $this->log("Failed call pcntl_signal(...)");
                }

                // 
                // Install signal handler for SIGHUP:
                // 
                if (pcntl_signal(SIGHUP, function ($signal) {
                            $this->log("Got wakeup signal $signal.");
                    })) {
                        $this->log("Installed signal handler (SIGHUP)");
                } else {
                        $this->log("Failed call pcntl_signal(...)");
                }

                // 
                // Process pending signals:
                // 
                if (!pcntl_signal_dispatch()) {
                        $this->log("Failed call pcntl_signal_dispatch()");
                }
        }

        /**
         * Setup error handler.
         */
        private function setupErrorHandler()
        {
                set_error_handler(function($code, $message, $file, $line) {
                        $this->log(sprintf("Trapped %s (%d) on %s:%d", $message, $code, $file, $line));
                        throw new ErrorException($message, 500, $code, $file, $line);
                });
        }

}
