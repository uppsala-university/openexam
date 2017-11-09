<?php

// 
// The source code is copyrighted, with equal shared rights, between the
// authors (see the file AUTHORS) and the OpenExam project, Uppsala University 
// unless otherwise explicit stated elsewhere.
// 
// File:    Archive.php
// Created: 2017-09-19 04:48:51
// 
// Author:  Anders Lövgren (QNET/BMC CompDept)
// 

namespace OpenExam\Library\Core\Exam;

use DateTime;
use OpenExam\Library\Render\Renderer;
use OpenExam\Models\Exam;
use Phalcon\Mvc\User\Component;
use RuntimeException;

/**
 * Exam archive class.
 * 
 * Use this class for create exam archives. Currently its only use case is for
 * rendering exams to PDF to be used for backup/archive or paper exams.
 *
 * @author Anders Lövgren (QNET/BMC CompDept)
 */
class Archive extends Component
{

        /**
         * Smallest accepted PDF file size.
         */
        const MIN_FILE_SIZE = 50000;

        /**
         * The exam model.
         * @var Exam 
         */
        private $_exam;
        /**
         * The target directory.
         * @var string 
         */
        private $_path;
        /**
         * The target file.
         * @var string 
         */
        private $_file;
        /**
         * The download name.
         * @var string 
         */
        private $_name;
        /**
         * Get HTML from this URL.
         * @var string 
         */
        private $_from;

        /**
         * Constructor.
         * @param Exam $exam The exam model.
         */
        public function __construct($exam)
        {
                $this->_exam = $exam;

                $this->_path = sprintf("%s/archive", $this->config->application->cacheDir);
                $this->_file = sprintf("%s/%d.pdf", $this->_path, $this->_exam->id);
                $this->_name = sprintf("%s.pdf", $this->_exam->name);

                $this->_from = $this->getSource($this->getToken());

                $this->prepare();
        }

        /**
         * Check that file exist.
         * @return boolean
         */
        public function exists()
        {
                return file_exists($this->_file);
        }

        /**
         * Create PDF file.
         */
        public function create()
        {
                $this->render();
        }

        /**
         * Delete PDF file.
         * @throws RuntimeException
         */
        public function delete()
        {
                if (!unlink($this->_file)) {
                        throw new RuntimeException("Failed unlink $this->_file");
                }
        }

        /**
         * Send PDF file.
         */
        public function send()
        {
                // 
                // Flush output buffering to get chunked mode:
                // 
                while (ob_get_level()) {
                        ob_end_clean();
                        ob_end_flush();
                }

                // 
                // Required by some browsers for actually caching:
                // 
                $expires = new DateTime();
                $expires->modify("+2 months");

                // 
                // Disable view:
                // 
                $this->view->disable();
                $this->view->finish();

                // 
                // Set headers for cache and chunked transfer mode:
                // 
                $this->response->setContentType('application/pdf', 'UTF-8');
                $this->response->setHeader("Cache-Control", "max-age=86400");
                $this->response->setHeader("Pragma", "public");
                $this->response->setHeader("Content-Disposition", sprintf('filename="%s"', $this->_name));
                $this->response->setExpires($expires);

                // 
                // Send response headers and file:
                // 
                $this->response->send();
                readfile($this->_file);
        }

        /**
         * Verify that PDF file is sane.
         * @return boolean
         */
        public function verify()
        {
                if (!file_exists($this->_file)) {
                        return false;
                }
                if (filesize($this->_file) < self::MIN_FILE_SIZE) {
                        return false;
                }
                if (filemtime($this->_file) < strtotime($this->_exam->updated)) {
                        return false;
                }

                return true;
        }

        /**
         * Check that caller can access archive.
         * @return boolean
         */
        public function accessable()
        {
                if ($this->user->roles->isAdmin()) {
                        return true;
                }
                if ($this->user->getPrincipalName() == $this->_exam->creator) {
                        return true;
                }
                return false;
        }

        /**
         * Render archive source to PDF.
         */
        private function render()
        {
                $config = array(array('page' => $this->_from));

                $render = $this->render->getRender(Renderer::FORMAT_PDF);
                $render->save($this->_file, $config);
        }

        /**
         * Create target directory if missing.
         * @throws RuntimeException
         */
        private function prepare()
        {
                if (file_exists($this->_path) && is_writable($this->_path)) {
                        return;
                }
                if (file_exists($this->_path) && is_readable($this->_path)) {
                        throw new RuntimeException("Target directory for exam archives is read-only");
                }
                if (!mkdir($this->_path, 0750, true)) {
                        throw new RuntimeException("Failed create target directory for exam archives");
                }
        }

        /**
         * Get source URL.
         * @param string $token The render token.
         * @return string
         */
        private function getSource($token)
        {
                $expand = $this->url->get(sprintf("exam/archive/%d", $this->_exam->id));
                $source = sprintf("http://%s%s?token=%s&user=%s&render=archive", $this->config->render->server, $expand, $token, $this->_exam->creator);
                return $source;
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
         * Get associated archive file.
         * @return string
         */
        public function getFilename()
        {
                return $this->_file;
        }

}
