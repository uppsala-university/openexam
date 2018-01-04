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
// File:    Download.php
// Created: 2017-12-18 18:09:33
// 
// Author:  Anders Lövgren (Computing Department at BMC, Uppsala University)
// 

namespace OpenExam\Library\Core\Exam\Result;

use OpenExam\Models\Exam;
use OpenExam\Models\Student;
use Phalcon\Mvc\User\Component;

/**
 * Download handler for exam results.
 *
 * @author Anders Lövgren (Computing Department at BMC, Uppsala University)
 */
class Download extends Component
{

        /**
         * Send student result.
         * 
         * @param Student $student The student model.
         */
        public function sendStudent($student)
        {
                $source = sprintf("%s.pdf", self::getPath($student->exam_id, $student->id));
                $target = sprintf("%s-%s.pdf", $student->code, $student->user);

                if (!file_exists($source)) {
                        $this->createFile($student);
                }

                $this->view->disable();
                $this->view->finish();

                $this->response->setFileToSend($source, $target);
                $this->response->setContentType('application/pdf', 'UTF-8');

                $this->response->send();
        }

        /**
         * Send exam archive.
         * 
         * @param Exam $exam The exam model.
         */
        public function sendExam($exam)
        {
                $compress = new Compress($exam->id);
                $this->sendArchive($compress);
        }

        /**
         * Send custom archive.
         * 
         * This method handles chunked transfer mode and disables output 
         * buffering to prevent memory exhausted fatal errors when sending
         * large files.
         * 
         * @param Compress $compress The compress object.
         */
        public function sendArchive($compress)
        {
                $source = sprintf("%s", $compress->getPath());
                $target = sprintf("%s", $compress->getName());

                if (!$compress->exist()) {
                        $compress->create();
                }

                $this->view->disable();
                $this->view->finish();

                while (ob_get_level()) {
                        ob_end_clean();
                        ob_end_flush();
                }

                $this->response->setFileToSend($source, "\"$target\"");
                $this->response->setContentType('application/zip', 'UTF-8');

                unset($source);
                unset($target);

                $this->response->send();
        }

}
