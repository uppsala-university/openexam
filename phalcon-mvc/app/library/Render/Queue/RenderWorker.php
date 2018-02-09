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
use Phalcon\Mvc\User\Component;

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
class RenderWorker extends Component
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
         * Don't log anything.
         */
        const LEVEL_NONE = 0;
        /**
         * The error condition log level.
         */
        const LEVEL_ERROR = 1;
        /**
         * The notice log level.
         */
        const LEVEL_NOTICE = 2;
        /**
         * The info log level.
         */
        const LEVEL_INFO = 3;
        /**
         * The successful log level.
         */
        const LEVEL_SUCCESS = 4;
        /**
         * The debug log level.
         */
        const LEVEL_DEBUG = 5;

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
         * The log level.
         * @var int 
         */
        private $_level = self::LEVEL_INFO;

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
         * Set log level.
         * @param int $level The log level.
         */
        public function setLogLevel($level)
        {
                $this->_level = $level;
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

                $this->log(self::LEVEL_INFO, "Starting");

                while (!$this->_done) {
                        if (!$this->fetch()) {
                                $this->reconnect();
                        }
                }

                $this->log(self::LEVEL_INFO, "Finished");
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
                        $this->log(self::LEVEL_ERROR, sprintf("Failed fetch job (%s)", $exception->getMessage()));
                        $this->block();
                        $failed = true;
                }

                return !isset($failed);
        }

        /**
         * Consume next job.
         * @return boolean 
         */
        private function consume()
        {
                $consumer = new RenderConsumer();

                if ($consumer->hasMissing()) {
                        $consumer->addMissing();
                }

                if (!$consumer->hasNext()) {
                        unset($consumer);
                        return false;
                }

                if (!($job = $consumer->getNext())) {
                        unset($consumer);
                        return false;
                }

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
                        $this->log(self::LEVEL_INFO, sprintf("Rendering job %d", $job->id));

                        $job->stime = time();
                        $this->_service->save($job->file, array(array('page' => $job->url)));
                        $job->etime = time();

                        $job->message = sprintf("Render time %d seconds", $job->etime - $job->stime);
                        $job->status = Render::STATUS_FINISH;
                } catch (Exception $exception) {
                        $this->log(self::LEVEL_ERROR, sprintf("Failed render job %d (%s)", $job->id, $exception->getMessage()));
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
                        $this->log(self::LEVEL_NOTICE, sprintf("Missing file property in job %d", $job->id));
                        return false;
                } else {
                        $dest = dirname($job->file);
                }

                if (!file_exists($dest)) {
                        $this->log(self::LEVEL_NOTICE, "The target directory $dest is missing");
                        return false;
                }
                if (!is_writable($dest)) {
                        $this->log(self::LEVEL_NOTICE, "The target directory $dest is not writable");
                        return false;
                }

                return true;
        }

        /**
         * Reconnect to database.
         */
        private function reconnect()
        {
                try {
                        $this->log(self::LEVEL_DEBUG, "Database: connecting to database...");
                        $this->dbread->connect();
                        $this->dbwrite->connect();
                        $this->dbaudit->connect();
                        $this->log(self::LEVEL_DEBUG, "Database: successful connected to database");
                } catch (Exception $exception) {
                        $this->log(self::LEVEL_ERROR, sprintf("Failed reconnect to database (%s)", $exception->getMessage()));
                }
        }

        /**
         * Go to sleep.
         */
        private function sleep()
        {
                $this->log(self::LEVEL_DEBUG, "Sleeping: No queued jobs");
                $this->block($this->_sleep);
                $this->log(self::LEVEL_DEBUG, "Wakeup: Polling for queued jobs...");
        }

        /**
         * Block execution for interval seconds.
         * @param int $interval Sleep number of seconds.
         */
        private function block($interval = self::BLOCK_INTERVAL)
        {
                if ($interval == self::BLOCK_INFINITE) {
                        pcntl_sigwaitinfo(array(SIGTERM, SIGHUP));
                } else {
                        sleep($interval);
                }
        }

        /**
         * Write log message.
         * 
         * @param int $level The log level.
         * @param string $message The log message.
         */
        private function log($level, $message)
        {
                if (empty($this->_logfile)) {
                        return;
                }
                if ($level > $this->_level) {
                        return;
                }

                if (!file_put_contents($this->_logfile, self::format($level, $message), FILE_APPEND)) {
                        trigger_error("Failed write message");
                }
        }

        /**
         * Format log message.
         * 
         * @param int $level The log level.
         * @param string $message The log message.
         * @return string
         */
        private static function format($level, $message)
        {
                return sprintf("%s [%d] (%s) %s\n", strftime("%Y-%m-%d %H:%M:%S"), getmypid(), self::level($level), $message);
        }

        /**
         * Get log level identifier.
         * @param int $level The log level.
         * @return string
         */
        private static function level($level)
        {
                switch ($level) {
                        case self::LEVEL_ERROR:
                                return '-';
                        case self::LEVEL_NOTICE:
                                return '!';
                        case self::LEVEL_INFO:
                                return 'i';
                        case self::LEVEL_SUCCESS;
                                return '+';
                        case self::LEVEL_DEBUG:
                                return 'd';
                }
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
                        $this->log(self::LEVEL_INFO, "The pcntl extension is missing. No signal handler installed.");
                        return;
                }

                // 
                // Install signal handler for SIGTERM:
                // 
                if (pcntl_signal(SIGTERM, function ($signal) {
                            $this->log(self::LEVEL_DEBUG, "Got terminate signal $signal. Setting exit flag for loop.");
                            $this->_done = true;
                    })) {
                        $this->log(self::LEVEL_DEBUG, "Installed signal handler (SIGTERM)");
                } else {
                        $this->log(self::LEVEL_ERROR, "Failed call pcntl_signal(...)");
                }

                // 
                // Install signal handler for SIGHUP:
                // 
                if (pcntl_signal(SIGHUP, function ($signal) {
                            $this->log(self::LEVEL_DEBUG, "Got wakeup signal $signal.");
                    })) {
                        $this->log(self::LEVEL_DEBUG, "Installed signal handler (SIGHUP)");
                } else {
                        $this->log(self::LEVEL_ERROR, "Failed call pcntl_signal(...)");
                }

                // 
                // Process pending signals:
                // 
                if (!pcntl_signal_dispatch()) {
                        $this->log(self::LEVEL_ERROR, "Failed call pcntl_signal_dispatch()");
                }
        }

        /**
         * Setup error handler.
         */
        private function setupErrorHandler()
        {
                set_error_handler(function($code, $message, $file, $line) {
                        $this->log(self::LEVEL_ERROR, sprintf("Trapped %s (%d) on %s:%d", $message, $code, $file, $line));
                        throw new ErrorException($message, 500, $code, $file, $line);
                });
        }

}
