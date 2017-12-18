<?php

// 
// The source code is copyrighted, with equal shared rights, between the
// authors (see the file AUTHORS) and the OpenExam project, Uppsala University 
// unless otherwise explicit stated elsewhere.
// 
// File:    Compress.php
// Created: 2017-12-18 14:00:10
// 
// Author:  Anders Lövgren (Computing Department at BMC, Uppsala University)
// 

namespace OpenExam\Library\Core\Exam\Result;

use Exception;
use OpenExam\Models\Exam;
use Phalcon\Mvc\User\Component;
use ZipArchive;

/**
 * Create compressed archive of result files.
 * 
 * Calling create() will block until all files exists. This is intentional as 
 * this class is intended to be used in conjunction with the render queue that 
 * is async by nature (renders result in parallel). Non-blocking behavior can
 * be used by passing false as second argument for create().
 *
 * @author Anders Lövgren (Computing Department at BMC, Uppsala University)
 */
class Compress extends Component
{

        /**
         * Default number of seconds to sleep on blocking mode.
         */
        const WAIT_TIMEOUT = 5;

        /**
         * The array of files.
         * @var array 
         */
        private $_files = array();
        /**
         * The target path.
         * @var string 
         */
        private $_path;
        /**
         * The archive name.
         * @var string 
         */
        private $_name;
        /**
         * The wait timeout in blocking mode.
         * @var int 
         */
        private $_wait = self::WAIT_TIMEOUT;

        /**
         * Constructor.
         * @param string $path The target path.
         */
        public function __construct($path = null, $name = null)
        {
                if (!isset($path)) {
                        $this->_path = $this->getAbsolute(time());
                } else {
                        $this->_path = $path;
                }

                if (!isset($path)) {
                        $this->_name = md5($path);
                } else {
                        $this->_name = $name;
                }
        }

        /**
         * Wait this number of seconds.
         * @param int $sec The wait timeout.
         */
        public function setTimeout($sec)
        {
                $this->_wait = $sec;
        }

        /**
         * Set target path.
         * The path is relative to the result directory.
         * 
         * @param string $path The file path.
         */
        public function setPath($path)
        {
                $this->_path = $this->getAbsolute($path);
        }

        /**
         * Get target path.
         * @return string
         */
        public function getPath()
        {
                return $this->_path;
        }

        /**
         * Set archive name.
         * @param string $name The archive name.
         */
        public function setName($name)
        {
                $this->_name = $name;
        }

        /**
         * Get archive name.
         * @return string
         */
        public function getName()
        {
                return $this->_name;
        }

        /**
         * Add file to archive.
         * 
         * The path is relative to result directory.
         * @param string $path The file path.
         */
        public function addFile($path, $name)
        {
                $this->_files[$name] = $this->getAbsolute($path);
        }

        /**
         * Add files from exam.
         * 
         * All result files in the render queue associated with this exam is
         * added to archive files array.
         * 
         * @param Exam $exam The exam model.
         */
        public function addExam($exam)
        {
                foreach ($exam->getRender("type = 'result'") as $render) {
                        $this->addFile($render->path, $render->user);
                }
        }

        /**
         * Create file archive.
         * 
         * If non-blocking mode is defined, then this method returns false if
         * any source file for archive is missing. In blocking mode, calling 
         * this method will put thread to sleep 
         * 
         * @param bool $block True for blocking mode.
         */
        public function create($block = true)
        {
                // 
                // Check that all files exists:
                // 
                while (true) {
                        if ($this->hasFiles()) {
                                break;
                        } elseif ($block) {
                                sleep($this->_wait);
                                continue;
                        } else {
                                return false;
                        }
                }

                // 
                // Add files to archive using student code.
                // 
                $zip = new ZipArchive();

                // 
                // Create new ZIP-archive:
                // 
                if (!($zip->open($this->_path, ZipArchive::CREATE))) {
                        throw new Exception($zip->getStatusString());
                }

                // 
                // Add files to archive:
                // 
                foreach ($this->_files as $name => $file) {
                        if (!$zip->addFile($file, $name)) {
                                throw new Exception($zip->getStatusString());
                        }
                }

                // 
                // Out work is done, close archive.
                // 
                $zip->close();
        }

        /**
         * Check if target archive exists.
         * @return bool
         */
        public function exist()
        {
                return file_exists($this->_path);
        }

        /**
         * Verify that calling exist() won't block.
         * @return boolean
         */
        public function verify()
        {
                return $this->hasFiles();
        }

        /**
         * Get absolute path.
         * @param string $path The file path.
         * @return string 
         */
        private function getAbsolute($path)
        {
                return sprintf("%s/%s", $this->config->application->cacheDir, $path);
        }

        /**
         * Check if all files are present.
         * @return boolean
         */
        private function hasFiles()
        {
                foreach ($this->_files as $file) {
                        if (!file_exists($file)) {
                                return false;
                        }
                }

                return true;
        }

}
