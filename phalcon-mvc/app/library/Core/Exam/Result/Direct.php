<?php

// 
// The source code is copyrighted, with equal shared rights, between the
// authors (see the file AUTHORS) and the OpenExam project, Uppsala University 
// unless otherwise explicit stated elsewhere.
// 
// File:    Result.php
// Created: 2016-04-19 11:40:23
// 
// Author:  Anders Lövgren (Computing Department at BMC, Uppsala University)
// 

namespace OpenExam\Library\Core\Exam\Result;

use Exception;
use OpenExam\Library\Core\Error;
use OpenExam\Library\Model\Exception as ModelException;
use OpenExam\Library\Render\Renderer;
use OpenExam\Models\Exam;
use OpenExam\Models\Student;

/**
 * Handles result generation and download.
 * 
 * The result is stored as PDF files in phalcon-mvc/cache/result. Directory 
 * structure is like this:
 * 
 * result/
 *   +-- eid            // Exam ID
 *         +-- sid.pdf  // Student ID
 *         +-- sid.pdf  // Student ID
 *        ...
 *   +-- eid
 *         +-- sid      // Student ID
 *        ...
 *  ...
 * 
 * This is an old class for generating result files and kept for future use
 * and reference. The new class that uses the render queue is prefered as it
 * enables parallel rendering of results.
 * 
 * @deprecated since version 2.1.2
 * @author Anders Lövgren (Computing Department at BMC, Uppsala University)
 */
class Direct extends Store
{

        /**
         * Smallest accepted PDF file size.
         */
        const MIN_FILE_SIZE = 50000;

        /**
         * Cleanup result for this exam.
         */
        protected function clean()
        {
                foreach ($this->_exam->students as $student) {
                        $this->delete($student);
                }

                parent::clean();
        }

        /**
         * Delete file associated with this student.
         * @param int|Student $sid The student.
         */
        public function delete($sid)
        {
                if (is_numeric($sid)) {
                        $student = $this->getStudent($sid);
                } else {
                        $student = $sid;
                }

                $target = sprintf("%s.pdf", self::getPath($this->_exam->id, $student->id));
                if (file_exists($target)) {
                        if (!unlink($target)) {
                                throw new Exception("Failed unlink student result.");
                        }
                }
                unset($target);
                unset($student);
        }

        /**
         * Create PDF for this student.
         * 
         * @param int|Student $sid The student ID or model.
         * @return boolean True if new file was created.
         * @throws Exception
         */
        public function createFile($sid)
        {
                if (is_numeric($sid)) {
                        $student = $this->getStudent($sid);
                } else {
                        $student = $sid;
                }

                // 
                // Destination file:
                // 
                $target = sprintf("%s.pdf", self::getPath($this->_exam->id, $student->id));

                // 
                // Check existing file.
                // 
                if (file_exists($target)) {
                        if ($this->_forced) {
                                unlink($target);
                        } else {
                                return false;
                        }
                }

                // 
                // Get authentication token for system local service:
                // 
                $token = $this->getToken();

                // 
                // The result view URL:
                // 
                $source = $this->getResultUrl($token, $student);

                // 
                // Check if file should be downloaded:
                // 
                if ($this->config->render->local) {
                        $this->downloadSource($source);
                }

                // 
                // Cleanup:
                // 
                unset($student);
                unset($token);

                // 
                // Create target directory if missing:
                // 
                if (!file_exists(dirname($target))) {
                        if (!mkdir(dirname($target), 0777, true)) {
                                throw new Exception("Failed create destination directory.");
                        }
                }

                // 
                // Page(s) that goes into generated PDF:
                //
                $settings = array(array('page' => $source));

                // 
                // Retry on HTTP 400 (Bad Request).
                //                 
                for ($i = 0; $i < 5; ++$i) {

                        $render = $this->render->getRender(Renderer::FORMAT_PDF);
                        $render->save($target, $settings);

                        if (file_exists($target) && filesize($target) > self::MIN_FILE_SIZE) {
                                return true;
                        } else {
                                sleep(5);
                        }
                }

                if (!file_exists($target)) {
                        throw new Exception("Failed create PDF document (target is missing).");
                }
                if (filesize($target) < self::MIN_FILE_SIZE) {
                        if (!unlink($target)) {
                                throw new Exception("Failed create PDF document (permission problem).");
                        } else {
                                throw new Exception("Failed create PDF document (failed generate file).");
                        }
                }
        }

        /**
         * Create all PDF files in this exam.
         */
        public function createFiles()
        {
                foreach ($this->_exam->students as $student) {
                        $this->createFile($student);
                }
        }

        /**
         * Create zip-file of exam results.
         */
        public function createArchive()
        {
                $compress = new Compress($this->_exam->id);

                // 
                // Check that all files exist and generate missing.
                // 
                foreach ($this->_exam->students as $student) {
                        $filename = sprintf("%s.pdf", self::getPath($this->_exam->id, $student->id));
                        if (!file_exists($filename)) {
                                $this->createFile($student);
                        }
                }

                // 
                // Delete existing archive.
                // 
                if (file_exists($compress->getPath())) {
                        unlink($compress->getPath());
                }

                // 
                // Add files to archive:
                // 
                foreach ($this->_exam->students as $student) {
                        $input = sprintf("%s.pdf", self::getPath($this->_exam->id, $student->id));
                        $local = sprintf("%s-%s.pdf", $student->code, $student->user);

                        $compress->addFile($input, $local);
                }

                // 
                // Create ZIP-archive:
                // 
                $compress->create();
        }

        /**
         * Create archives for all exams where endtime is after date and
         * decoded status is true.
         * 
         * @param string|int $date The start date.
         * @throws ModelException
         */
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
                        $result = new Direct($exam);
                        $result->createArchive();
                        unset($result);
                }
        }

        /**
         * Get authentication token for system local service
         * @return string
         */
        private function getToken()
        {
                if (file_exists($this->config->render->token)) {
                        return file_get_contents($this->config->render->token);
                } else {
                        return $this->config->render->token;
                }
        }

        /**
         * Get result view URL.
         * @param string $token The authentication token.
         * @param Student $student The student model.
         * @return string
         */
        private function getResultUrl($token, $student)
        {
                $expand = $this->url->get(sprintf("result/%d/view/%d", $this->_exam->id, $student->id));
                $source = sprintf("http://%s%s?token=%s&user=%s&render=result", $this->config->render->server, $expand, $token, $student->user);
                return $source;
        }

        /**
         * Download source to local file.
         * 
         * @param string $source The source URL.
         * @throws Exception
         */
        private function downloadSource(&$source)
        {
                if (!($content = file_get_contents($source))) {
                        throw new Exception("Failed download content.");
                }
                if (!($tmpfile = tempnam(sys_get_temp_dir(), 'result-source'))) {
                        throw new Exception("Failed create temporary file.");
                } else {
                        $dstfile = sprintf("%s.html", $tmpfile);
                        unlink($tmpfile);
                }
                if (!(file_put_contents($dstfile, $content))) {
                        throw new Exception("Failed save content.");
                } else {
                        $source = $dstfile;
                }
        }

}
