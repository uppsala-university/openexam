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
// File:    RenderConsumer.php
// Created: 2017-12-07 01:22:47
// 
// Author:  Anders Lövgren (QNET)
// 

namespace OpenExam\Library\Render\Queue;

use OpenExam\Library\Render\Exception;
use OpenExam\Models\Render;
use Phalcon\Mvc\User\Component;

/**
 * The queue consumer.
 * 
 * Provides an abstration of the render queue (model) for render workers. The 
 * message passed between them is using render jobs.
 *
 * @author Anders Lövgren (QNET)
 */
class RenderConsumer extends Component {

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
   * Number of seconds for render job to complete.
   */
  const MISSING_RENDER_TIME = 600;

  /**
   * The log level.
   * @var int
   */
  private $_level = self::LEVEL_INFO;
  /**
   * Write processing to log file.
   * @var string
   */
  private $_logfile;

  /**
   * Constructor.
   * @param Renderer $service The render service.
   */
  public function __construct($logfile, $loglevel) {
    $this->_logfile = $logfile;
    $this->_level = $loglevel;
  }

  /**
   * Add missing render job as queued.
   */
  public function addMissing() {
    //
    // Because queued updates on every save, it's basicly lastChanged.
    // Find missing render job:
    //
    if (!($jobs = Render::find(sprintf("status = 'render' AND queued < '%s' ", strftime("%Y-%m-%d %T", time() - self::MISSING_RENDER_TIME))))) {
      return false;
    }

    //
    // Add missing job as queued:
    //
    foreach ($jobs as $job) {
      $job->status = Render::STATUS_QUEUED;
      $job->finish = null;

      $job->file = sprintf("%s/%s", $this->config->application->cacheDir, $job->path);
      $job->lock = sprintf("%s/%s.lock", $this->config->application->cacheDir, $job->path);

      @unlink($job->lock);
      if (!$job->save()) {
        throw new Exception($job->getMessages()[0]);
      }
    }
  }

  /**
   * Check if queued render job exists.
   * @return boolean
   */
  public function hasNext() {
    //
    // Find next queued render job:
    //
    if ((Render::count("status = 'queued'") > 0)) {
      return true;
    } else {
      return false;
    }
  }

  /**
   * Get next render job.
   * @return Render
   */
  public function getNext() {
    //
    // Find next queued render job:
    //
    if (!($job = Render::findFirst("status = 'queued'"))) {
      return false;
    }

    //
    // Set render status, destination and lock properties:
    //
    $job->status = Render::STATUS_RENDER;
    $job->file = sprintf("%s/%s", $this->config->application->cacheDir, $job->path);
    $job->lock = sprintf("%s/%s.lock", $this->config->application->cacheDir, $job->path);

    //
    // Check if job is locked by another render host:
    //
    if (file_exists($job->lock)) {
      $job->save();
      return false;
    } else {
      $this->log(self::LEVEL_DEBUG, "Creating lockfile: " . $job->lock);
      touch($job->lock);
    }

    //
    // Refresh job status, but remove lock if failed:
    //
    //
    if (!$job->save()) {
      unlink($job->lock);
      throw new Exception($job->getMessages()[0]);
    }

    return $job;
  }

  /**
   * Set render result.
   * @param Render $job The render job.
   */
  public function setResult($job) {
    //
    // Update job status:
    //
    if ($job->status != Render::STATUS_FINISH &&
      $job->status != Render::STATUS_FAILED) {
      $job->status = Render::STATUS_FINISH;
    }

    //
    // Use proper database timestamp:
    //
    $job->finish = strftime("%Y-%m-%d %T");

    //
    // Cleanup existing lock:
    //
    if (file_exists($job->lock)) {
      unlink($job->lock);
    }

    //
    // Refresh job status:
    //
    if (!$job->save()) {
      throw new Exception($job->getMessages()[0]);
    }
  }


  /**
   * Write log message.
   *
   * @param int $level The log level.
   * @param string $message The log message.
   */
  private function log($level, $message) {
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
  private static function format($level, $message) {
    return sprintf("%s [%d] (%s) %s\n", strftime("%Y-%m-%d %H:%M:%S"), getmypid(), self::level($level), $message);
  }

  /**
   * Get log level identifier.
   * @param int $level The log level.
   * @return string
   */
  private static function level($level) {
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



}
