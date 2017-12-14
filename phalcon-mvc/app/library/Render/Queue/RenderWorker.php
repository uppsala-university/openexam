<?php

// 
// The source code is copyrighted, with equal shared rights, between the
// authors (see the file AUTHORS) and the OpenExam project, Uppsala University 
// unless otherwise explicit stated elsewhere.
// 
// File:    RenderWorker.php
// Created: 2017-12-07 01:04:39
// 
// Author:  Anders Lövgren (QNET)
// 

namespace OpenExam\Library\Render\Queue;

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
 * waiting for a signal, pass the BLOCK_INFINITE constant as sleep value.
 *
 * @author Anders Lövgren (QNET)
 */
class RenderWorker
{

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
         * Process render jobs.
         */
        public function process()
        {
                declare(ticks = 1);

                $this->setupSignalHandler();
                $this->log("Starting");

                while (!$this->_done) {
                        $consumer = new RenderConsumer();

                        if ($consumer->hasNext()) {
                                $job = $consumer->getNext();
                                $this->render($job);
                                $consumer->setResult($job);
                        } else {
                                unset($consumer);
                                $this->sleep();
                        }
                }

                $this->log("Finished");
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

                $this->log(sprintf("Rendering job %d", $job->id));

                $job->stime = time();
                $this->_service->save($job->file, array(array('page' => $job->url)));
                $job->etime = time();

                $job->message = sprintf("Render time %d seconds", $job->etime - $job->stime);
                $job->status = Render::STATUS_FINISH;
        }

        /**
         * Check common problems.
         * 
         * @param Render $job The render model.
         * @return boolean
         */
        private function check($job)
        {
                $dest = dirname($job->file);

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
                            $this->log("Got terminate signal $signal. Setting loop exit flag.");
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

}
