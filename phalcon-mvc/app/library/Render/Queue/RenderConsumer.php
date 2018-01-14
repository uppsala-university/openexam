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
                if ($job->status != Render::STATUS_FINISH &&
                    $job->status != Render::STATUS_FAILED) {
                        $job->status = Render::STATUS_FINISH;
                }

                $job->finish = strftime("%Y-%m-%d %T");
                $job->save();
        }

}
