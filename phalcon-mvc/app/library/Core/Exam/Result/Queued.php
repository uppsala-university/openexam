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
// File:    Queued.php
// Created: 2017-12-18 12:43:58
// 
// Author:  Anders Lövgren (Computing Department at BMC, Uppsala University)
// 

namespace OpenExam\Library\Core\Exam\Result;

use Exception;
use OpenExam\Library\Core\Error;
use OpenExam\Library\Render\Queue\RenderQueue;
use OpenExam\Models\Exam;
use OpenExam\Models\Render;

/**
 * Result generation using render queue.
 *
 * @author Anders Lövgren (Computing Department at BMC, Uppsala University)
 */
class Queued extends Store
{

        public function clean()
        {
                foreach ($this->_exam->render as $render) {
                        $path = sprintf("%s/%s", $this->config->application->cacheDir, $render->path);
                        if (!unlink($path)) {
                                throw new Exception("Failed unlink student result.");
                        }
                        if (!$render->delete()) {
                                throw new ModelException($render->getMessages()[0], Error::INTERNAL_SERVER_ERROR);
                        }
                }

                parent::clean();
        }

        public function createArchive()
        {
                $compress = new Compress($this->_exam->id);
                $compress->addExam($this->_exam);
                $compress->create();
        }

        public function createFile($sid)
        {
                if (is_numeric($sid)) {
                        $student = $this->getStudent($sid);
                } else {
                        $student = $sid;
                }

                $queue = new RenderQueue();
                $model = new Render();
                $model->assign(array(
                        'exam_id' => $this->_exam->id,
                        'user'    => $student->user,
                        'type'    => 'result'
                ));

                $queue->addJob($model, $student);
        }

        public function createFiles()
        {
                foreach ($this->_exam->students as $student) {
                        $this->createFile($student);
                }
        }

        public function delete($sid)
        {
                if (is_numeric($sid)) {
                        $student = $this->getStudent($sid);
                } else {
                        $student = $sid;
                }

                $queue = new RenderQueue();
                $model = $queue->findJob($this->_exam->id, 'result', $student->user);

                if ($model) {
                        $file = sprintf("%s/result/%s", $this->config->application->cacheDir, $model->path);
                        if (file_exists($file)) {
                                unlink($file);
                        }
                        if (!$model->delete()) {
                                throw new Exception($model->getMessages()[0]);
                        }
                }
        }

        public static function createArchives($date)
        {
                if (is_numeric($date)) {
                        $date = strftime("%Y-%m-%d %H:%M:%S", $date);
                }

                if (($exams = Exam::find(array(
                            'conditions' => "endtime > :date: AND decoded = 'Y'",
                            'bind'       => array('date' => $date)
                    ))) == false) {
                        throw new ModelException("Failed get exam models.", Error::INTERNAL_SERVER_ERROR);
                }

                foreach ($exams as $exam) {
                        $result = new Queued($exam);
                        $result->createArchive();
                        unset($result);
                }
        }

        public function hasFile($student)
        {
                if (parent::hasFile($student)) {
                        return true;
                }
                return $this->_exam->render->count(
                        "user = '" . $student->user . "'"
                    ) > 0;
        }

}
