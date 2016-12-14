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
//          Anders Lövgren (BMC-IT)
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
                // Fetch shared resources filtered on sharing levels:
                // 
                if (!($sres = Resource::find(array(
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
                        throw new \Exception("Failed fetch shared resources");
                }

                // 
                // Fetch personal resource files:
                // 
                if (!($pres = Resource::find(array(
                            'conditions' => "user = :user: AND exam_id != :exam:",
                            'bind'       => array(
                                    'user' => $loggedIn,
                                    'exam' => $examid
                            ),
                            'order'      => 'id desc'
                    )))) {
                        throw new \Exception("Failed fetch personal resources");
                }

                // 
                // Filter shared resources on mime-type:
                // 
                $simages = $sres->filter(function($resource) {
                        if ($resource->type == 'image') {
                                return $resource;
                        }
                });
                $svideos = $sres->filter(function($resource) {
                        if ($resource->type == 'video') {
                                return $resource;
                        }
                });
                $saudios = $sres->filter(function($resource) {
                        if ($resource->type == 'audio') {
                                return $resource;
                        }
                });
                $sothers = $sres->filter(function($resource) {
                        if (!in_array($resource->type, array('image', 'video', 'audio'))) {
                                return $resource;
                        }
                });

                // 
                // Filter personal resources on mime-type:
                // 
                $pimages = $pres->filter(function($resource) {
                        if ($resource->type == 'image') {
                                return $resource;
                        }
                });
                $pvideos = $pres->filter(function($resource) {
                        if ($resource->type == 'video') {
                                return $resource;
                        }
                });
                $paudios = $pres->filter(function($resource) {
                        if ($resource->type == 'audio') {
                                return $resource;
                        }
                });
                $pothers = $pres->filter(function($resource) {
                        if (!in_array($resource->type, array('image', 'video', 'audio'))) {
                                return $resource;
                        }
                });

                $this->view->setVar('sres', array(
                        "images" => $simages,
                        "videos" => $svideos,
                        "audios" => $saudios,
                        "others" => $sothers
                ));

                $this->view->setVar('pres', array(
                        "images" => $pimages,
                        "videos" => $pvideos,
                        "audios" => $paudios,
                        "others" => $pothers
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
         * Send media file.
         * 
         * @param string $type The media file type (e.g image, video).
         * @param string $file The file name.
         * @throws \Exception
         */
        public function viewAction($type, $file)
        {
                // 
                // Sanity check:
                // 
                if (empty($type) || empty($file)) {
                        throw new \Exception('Invalid request', Error::PRECONDITION_FAILED);
                }

                // 
                // Prevent disclose of system files:
                // 
                if (strpos($file, '/') != false ||
                    strpos($type, '/') != false) {
                        throw new \Exception('Invalid character in filename', Error::NOT_ACCEPTABLE);
                }

                // 
                // Check that file exists:
                // 
                $path = sprintf("%s/%ss/%s", $this->config->application->mediaDir, $type, $file);

                if (!file_exists($path)) {
                        throw new \Exception("Can't located requested file", Error::NOT_FOUND);
                }
                if (!is_file($path)) {
                        throw new \Exception("Requested resource is not a file", Error::NOT_FOUND);
                }

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
                $expires = new \DateTime();
                $expires->modify("+2 months");

                // 
                // Disable view:
                // 
                $this->view->disable();
                $this->view->finish();

                // 
                // Set file and headers for transfer:
                // 
                $this->response->setFileToSend($path);
                $this->response->setContentType(mime_content_type($path));
                $this->response->setHeader("Cache-Control", "max-age=86400");
                $this->response->setHeader("Pragma", "public");
                $this->response->setExpires($expires);

                // 
                // Send content including file:
                // 
                $this->response->send();
        }

}
