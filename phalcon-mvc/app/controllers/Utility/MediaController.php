<?php

// 
// The source code is copyrighted, with equal shared rights, between the
// authors (see the file AUTHORS) and the OpenExam project, Uppsala University 
// unless otherwise explicit stated elsewhere.
// 
// File:    ExamController.php
// Created: 2014-09-19 11:15:15
// 
// Author:  Ahsan Shahzad (MedfarmDoIT)
// 

namespace OpenExam\Controllers\Utility;

use OpenExam\Controllers\ControllerBase;
use OpenExam\Library\UploadHandler;

/**
 * Controller for loading media resources
 *
 * @author Ahsan Shahzad (MedfarmDoIT)
 */
class MediaController extends ControllerBase
{

        /**
         * Shows media library interface to upload / select lib resources
         * utility/media/library
         */
        public function libraryAction()
        {
                // fetch and pass data to view
                // set rendering level
                $this->view->setRenderLevel(\Phalcon\Mvc\View::LEVEL_ACTION_VIEW);
        }

        /**
         * Upload media resources
         * utility/media/upload
         */
        public function uploadAction()
        {
                $this->view->disable();

                // find media type of this file to set file upload directory
                $files = $this->request->getUploadedFiles();
                preg_match('/(.*)\/.*/', $files[0]->getRealType(), $mediaType);
                $uploadDir = $this->config->application->mediaDir . $mediaType[1] . "s/";
                $uploadUrl = $this->url->get('utility/media/view/' . $mediaType[1]);

                // upload file
                $uploadHandler = new UploadHandler(array(
                        'upload_dir' => $uploadDir,
                        'upload_url' => $uploadUrl."/",
                ));
        }

        /**
         * Dumps media file content
         * 
         * @param string $type Media type (e.g image, video)
         * @param string $file File name
         * @return 
         * @throws \Exception
         */
        public function viewAction($type, $file)
        {
                $this->view->disable();

                if (empty($type) || empty($file)) {
                        throw new \Exception('Invalid request');
                }

                $path = $this->config->application->mediaDir . $type . "s" . DIRECTORY_SEPARATOR . $file;

                if (is_file($path) === true) {
                        set_time_limit(0);

                        while (ob_get_level() > 0) {
                                ob_end_clean();
                        }

                        $size = sprintf('%u', filesize($path));
                        $speed = (is_null($speed) === true) ? $size : intval($speed) * 1024;
                        $contentType = finfo_file(finfo_open(FILEINFO_MIME_TYPE), $path);
                        header('Expires: 0');
                        header('Pragma: public');
                        header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
                        header('Content-Type: ' . $contentType . '; charset=utf-8');
                        header('Content-Length: ' . $size);
                        header('Content-Disposition: attachment; filename="' . basename($path) . '"');
                        header('Content-Transfer-Encoding: binary');

                        for ($i = 0; $i <= $size; $i = $i + $speed) {
                                echo file_get_contents($path, false, null, $i, $speed);

                                while (ob_get_level() > 0) {
                                        ob_end_clean();
                                }

                                flush();
                                sleep(1);
                        }

                        exit();
                } else {
                        throw new \Exception("File not found");
                }
        }

}
