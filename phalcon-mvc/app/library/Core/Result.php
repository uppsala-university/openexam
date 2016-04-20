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

namespace OpenExam\Library\Core;

use OpenExam\Library\Model\Exception as ModelException;
use OpenExam\Library\Render\Renderer;
use OpenExam\Models\Exam;
use OpenExam\Models\Student;
use Phalcon\Mvc\User\Component;
use ZipArchive;

/**
 * Handles result generation and download.
 * 
 * The result is stored as PDF files in phalcon-mvc/cache/results. Directory 
 * structure is like this:
 * 
 * results/
 *   +-- eid            // Exam ID
 *         +-- sid.pdf  // Student ID
 *         +-- sid.pdf  // Student ID
 *        ...
 *   +-- eid
 *         +-- sid      // Student ID
 *        ...
 *  ...
 *
 * @author Anders Lövgren (Computing Department at BMC, Uppsala University)
 */
class Result extends Component
{

        /**
         * Smallest accepted PDF file size.
         */
        const MIN_FILE_SIZE = 50000;

        /**
         * @var Exam 
         */
        private $exam;
        /**
         * Force generate files even if existing.
         * @var bool 
         */
        private $forced = false;

        /**
         * Constructor.
         * @param int|Exam $eid The exam.
         * @throws ModelException
         */
        public function __construct($eid)
        {
                if (!is_numeric($eid)) {
                        $this->exam = $eid;
                } elseif (!($this->exam = Exam::findFirst($eid))) {
                        throw new ModelException("Failed find target exam.", Error::PRECONDITION_FAILED);
                }

                if ($this->exam->decoded == false) {
                        throw new ModelException("Result can't be downloaded before exam has been decoded.", Error::LOCKED);
                }
        }

        /**
         * Force generate files even if existing.
         * @param bool $enable
         */
        public function setForced($enable = true)
        {
                $this->forced = $enable;
        }

        /**
         * Cleanup result for this exam.
         */
        public function clean()
        {
                foreach ($this->exam->students as $student) {
                        $this->delete($student);
                }

                $target = sprintf("%s.zip", self::getPath($this->exam->id));
                if (file_exists($target)) {
                        if (!unlink($target)) {
                                throw new \Exception("Failed unlink result archive.");
                        }
                }

                $target = sprintf("%s.xls", self::getPath($this->exam->id));
                if (file_exists($target)) {
                        if (!unlink($target)) {
                                throw new \Exception("Failed unlink result spreadsheet.");
                        }
                }
                
                $target = sprintf("%s", self::getPath($this->exam->id));
                if (file_exists($target)) {
                        if (!rmdir($target)) {
                                throw new \Exception("Failed delete result directory.");
                        }
                }
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

                $target = sprintf("%s.pdf", self::getPath($this->exam->id, $student->id));
                if (file_exists($target)) {
                        if (!unlink($target)) {
                                throw new \Exception("Failed unlink student result.");
                        }
                }
        }

        /**
         * Create PDF for this student.
         * @param int|Student $sid The student.
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
                $target = sprintf("%s.pdf", self::getPath($this->exam->id, $student->id));

                // 
                // Check existing file.
                // 
                if (file_exists($target)) {
                        if ($this->forced) {
                                unlink($target);
                        } else {
                                return;
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
                // Create target directory if missing:
                // 
                if (!file_exists(dirname($target))) {
                        if (!mkdir(dirname($target), 0777, true)) {
                                throw new \Exception("Failed create destination directory.");
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
                                return;
                        } else {
                                sleep(5);
                        }
                }

                if (!file_exists($target)) {
                        throw new \Exception("Failed create PDF document (missing).");
                }
                if (filesize($target) < self::MIN_FILE_SIZE) {
                        throw new \Exception("Failed create PDF document (bad request).");
                }
        }

        /**
         * Create all PDF files in this exam.
         */
        public function createFiles()
        {
                foreach ($this->exam->students->fi as $student) {
                        $this->createFile($student);
                }
        }

        /**
         * Create zip-file of exam results.
         */
        public function createArchive()
        {
                $target = sprintf("%s.zip", self::getPath($this->exam->id));

                // 
                // Check that all files exist and generate missing.
                // 
                foreach ($this->exam->students as $student) {
                        $filename = sprintf("%s.pdf", self::getPath($this->exam->id, $student->id));
                        if (!file_exists($filename)) {
                                $this->createFile($student);
                        }
                }

                // 
                // Delete existing archive.
                // 
                if (file_exists($target)) {
                        unlink($target);
                }

                // 
                // Add files to archive using student code.
                // 
                $zip = new ZipArchive();

                if (!($zip->open($target, ZipArchive::CREATE))) {
                        throw new \Exception($zip->getStatusString());
                }
                foreach ($this->exam->students as $student) {
                        $input = sprintf("%s.pdf", self::getPath($this->exam->id, $student->id));
                        $local = sprintf("%s-%s.pdf", $student->code, $student->user);

                        if (!$zip->addFile($input, $local)) {
                                throw new \Exception($zip->getStatusString());
                        }
                }
                $zip->close();
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
                        $result = new Result($exam);
                        $result->createArchive();
                }
        }

        /**
         * Download PDF for this student.
         * @param int|Student $sid The student.
         */
        public function downloadFile($sid)
        {
                if (is_numeric($sid)) {
                        $student = $this->getStudent($sid);
                } else {
                        $student = $sid;
                }

                $source = sprintf("%s.pdf", self::getPath($this->exam->id, $student->id));
                $target = sprintf("%s-%s.pdf", $student->code, $student->user);

                if (!file_exists($source)) {
                        $this->createFile($student);
                }

                $this->view->disable();

                $this->response->setContentType('application/pdf', 'UTF-8');
                $this->response->setFileToSend($source, $target);

                $this->response->send();
        }

        /**
         * Download results from this exam as zip-file.
         */
        public function downloadArchive()
        {
                $source = sprintf("%s.zip", self::getPath($this->exam->id));
                $target = sprintf("%s.zip", $this->exam->name);

                if (!file_exists($source)) {
                        $this->createArchive();
                }

                $this->view->disable();

                $this->response->setContentType('application/zip', 'UTF-8');
                $this->response->setFileToSend($source, "\"$target\"");

                $this->response->send();
        }

        /**
         * Check if caller can access student result.
         * 
         * This method modifies the argument if unset and caller is found
         * in the students collection on this exam.
         * 
         * @param int $sid The student ID.
         * @return boolean
         */
        public function canAccess(&$sid)
        {
                // 
                // Admins and staff can always access student result.
                // 
                if ($this->user->roles->isAdmin() ||
                    $this->user->roles->isStaff($this->exam->id)) {
                        return true;
                }

                // 
                // Find student in students on this exam.
                // 
                if (!isset($sid)) {
                        $found = $this->exam->students->filter(function($student) {
                                if ($student->user == $this->user->getPrincipalName()) {
                                        return $student;
                                }
                        });
                } else {
                        $found = $this->exam->students->filter(function($student) use($sid) {
                                if ($student->id == $sid) {
                                        return $student;
                                }
                        });
                }

                // 
                // Check filtered students result:
                // 
                if (count($found) != 1) {
                        return false;
                }
                if ($found[0]->user != $this->user->getPrincipalName()) {
                        return false;
                }

                if (!isset($sid)) {
                        $sid = $found[0]->id;
                }

                return true;
        }

        /**
         * Get student model.
         * 
         * @param int $sid The student ID.
         * @return Student
         * @throws ModelException
         */
        private function getStudent($sid)
        {
                if (!($student = Student::findFirst($sid))) {
                        throw new ModelException("Failed find student.", Error::PRECONDITION_FAILED);
                }

                return $student;
        }

        /**
         * Get directory path.
         * 
         * @param int $eid The exam ID.
         * @param int $sid The student ID.
         * @return string
         */
        private function getPath($eid = 0, $sid = 0)
        {
                if ($eid == 0) {
                        return sprintf("%s/results", $this->config->application->cacheDir);
                } elseif ($sid == 0) {
                        return sprintf("%s/results/%d", $this->config->application->cacheDir, $eid);
                } else {
                        return sprintf("%s/results/%d/%d", $this->config->application->cacheDir, $eid, $sid);
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
                $expand = $this->url->get(sprintf("result/%d/view/%d", $this->exam->id, $student->id));
                $source = sprintf("http://localhost/%s?token=%s&user=%s", $expand, $token, $student->user);
                return $source;
        }

}
