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

use OpenExam\Controllers\GuiController;
use OpenExam\Library\Core\Error;
use OpenExam\Library\Security\Roles;
use OpenExam\Models\Resource;
use Phalcon\Mvc\View;

require_once (EXTERN_DIR . 'UploadHandler.php');

/**
 * Controller for loading media resources
 *
 * @author Ahsan Shahzad (MedfarmDoIT)
 */
class MediaController extends GuiController
{

        /**
         * Shows media library interface to upload / select lib resources
         * utility/media/library
         * 
         * @param int $exam_id The exam ID (POST).
         */
        public function libraryAction()
        {
                $examid = $this->request->get('exam_id', 'int');

                if (!$examid) {
                        throw new \Exception("Expected exam ID", Error::PRECONDITION_FAILED);
                }

                $loggedIn = $this->user->getPrincipalName();
                $this->user->setPrimaryRole(Roles::CONTRIBUTOR);

                // 
                // Fetch data filtered on the various sharing levels:
                // 
                if (!($resources = Resource::find(array(
                            'conditions' => "
                                    (shared = :private: AND user = ?2) 
                                        OR 
                                    (shared = :exam: AND exam_id = ?1) 
                                        OR 
                                    (shared = :group: AND user = ?3) 
                                        OR 
                                    (shared = :global:)",
                            'bind'       => array(
                                    'private' => Resource::NOT_SHARED,
                                    'exam'    => Resource::SHARED_EXAM,
                                    'group'   => Resource::SHARED_GROUP,
                                    'global'  => Resource::SHARED_GLOBAL,
                                    1         => $examid,
                                    2         => $loggedIn,
                                    3         => $loggedIn
                            ),
                            'order'      => 'shared,id desc'
                    )))) {
                        throw new \Exception("Failed fetch resources");
                }

                // 
                // Filter out mime type in result set:
                // 
                $images = $resources->filter(function($resource) {
                        if ($resource->type == 'image') {
                                return $resource;
                        }
                });
                $videos = $resources->filter(function($resource) {
                        if ($resource->type == 'video') {
                                return $resource;
                        }
                });
                $audios = $resources->filter(function($resource) {
                        if ($resource->type == 'audio') {
                                return $resource;
                        }
                });
                $others = $resources->filter(function($resource) {
                        if (!in_array($resource->type, array('image', 'video', 'audio'))) {
                                return $resource;
                        }
                });

                $this->view->setVar('resources', array(
                        "images" => $images,
                        "videos" => $videos,
                        "audios" => $audios,
                        "others" => $others
                ));

                // 
                // No views can handle exceptions, so reset primary role:
                // 
                $this->user->setPrimaryRole(null);
                $this->view->setRenderLevel(View::LEVEL_ACTION_VIEW);
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
                $uploadHandler = new \UploadHandler(array(
                        'upload_dir' => $uploadDir,
                        'upload_url' => $uploadUrl . "/",
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
                        header('Content-Disposition: inline; filename="' . basename($path) . '"'); //attachment
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
