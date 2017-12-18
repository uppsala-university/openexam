<?php

// 
// The source code is copyrighted, with equal shared rights, between the
// authors (see the file AUTHORS) and the OpenExam project, Uppsala University 
// unless otherwise explicit stated elsewhere.
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
                $source = sprintf("%s.zip", $compress->getPath());
                $target = sprintf("%s.zip", $compress->getName());

                if (!file_exists($compress->getPath())) {
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
