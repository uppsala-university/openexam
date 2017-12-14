<?php

// 
// The source code is copyrighted, with equal shared rights, between the
// authors (see the file AUTHORS) and the OpenExam project, Uppsala University 
// unless otherwise explicit stated elsewhere.
// 
// File:    RenderConsumer.php
// Created: 2017-12-07 01:22:47
// 
// Author:  Anders Lövgren (QNET)
// 

namespace OpenExam\Library\Render\Queue;

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
class RenderConsumer extends Component
{

        /**
         * Check if queued render job exists.
         * @return boolean
         */
        public function hasNext()
        {
                if (($count = Render::count("status = 'queued'"))) {
                        return true;
                } else {
                        return false;
                }
        }

        /**
         * Get next render job.
         * @return Render
         */
        public function getNext()
        {
                if (($job = Render::findFirst("status = 'queued'"))) {
                        $job->status = Render::STATUS_RENDER;
                        $job->file = sprintf("%s/%s", $this->config->application->cacheDir, $job->path);
                        $job->save();

                        return $job;
                }
        }

        /**
         * Set render result.
         * @param Render $job The render job.
         */
        public function setResult($job)
        {
                if ($job->status != Render::STATUS_FINISH ||
                    $job->status != Render::STATUS_FAILED) {
                        $job->status = Render::STATUS_FINISH;
                }

                $job->finish = strftime("%Y-%m-%d %T");
                $job->save();
        }

}
