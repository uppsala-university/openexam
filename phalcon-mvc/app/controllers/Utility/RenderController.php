<?php

// 
// The source code is copyrighted, with equal shared rights, between the
// authors (see the file AUTHORS) and the OpenExam project, Uppsala University 
// unless otherwise explicit stated elsewhere.
// 
// File:    RenderController.php
// Created: 2017-10-30 05:22:32
// 
// Author:  Anders Lövgren (QNET)
// 

namespace OpenExam\Controllers\Utility;

use OpenExam\Controllers\GuiController;
use OpenExam\Library\Core\Error;
use OpenExam\Library\Render\Exception;
use OpenExam\Library\Render\Queue\RenderQueue;
use OpenExam\Library\Render\Renderer;
use OpenExam\Library\Security\Roles;
use OpenExam\Models\Exam;
use OpenExam\Models\Render;

/**
 * Generic render controller.
 * 
 * This controller can be called to render an URL as a PDF document or
 * image file using the render service.
 *
 * @author Anders Lövgren (QNET)
 */
class RenderController extends GuiController
{

        /**
         * Render PDF document.
         * 
         * @param string $url The source URL.
         * @param string $filename The download filename.
         */
        public function pdfAction()
        {
                $this->checkAccess();
                $render = $this->getRender('pdf', 'file.pdf');
                $render->send($render->filename, array(array('page' => $render->url)));
        }

        /**
         * Render image file.
         * 
         * @param string $url The source URL.
         * @param string $filename The download filename.
         * @param string $format The image file format (i.e. png).
         */
        public function imageAction($format = 'png')
        {
                $this->checkAccess();
                $render = $this->getRender('image', "file.$format");
                $render->setFormat($format);
                $render->send($render->filename, array('in' => $render->url));
        }

        /**
         * Render PNG image.
         * 
         * @param string $url The source URL.
         * @param string $filename The download filename.
         */
        public function imagePngAction()
        {
                $this->checkAccess();
                $render = $this->getRender('image', 'file.png');
                $render->setFormat('png');
                $render->send($render->filename, array('in' => $render->url));
        }

        /**
         * Render JPEG image.
         * 
         * @param string $url The source URL.
         * @param string $filename The download filename.
         */
        public function imageJpgAction()
        {
                $this->checkAccess();
                $render = $this->getRender('image', 'file.jpg');
                $render->setFormat('jpg');
                $render->send($render->filename, array('in' => $render->url));
        }

        /**
         * Render BMP image.
         * 
         * @param string $url The source URL.
         * @param string $filename The download filename.
         */
        public function imageBmpAction()
        {
                $this->checkAccess();
                $render = $this->getRender('image', 'file.bmp');
                $render->setFormat('bmp');
                $render->send($render->filename, array('in' => $render->url));
        }

        /**
         * Render SVG graphics.
         * 
         * @param string $url The source URL.
         * @param string $filename The download filename.
         */
        public function imageSvgAction()
        {
                $this->checkAccess();
                $render = $this->getRender('image', 'file.svg');
                $render->setFormat('svg');
                $render->send($render->filename, array('in' => $render->url));
        }

        /**
         * Show decoder view for result download.
         * @param int $eid The exam ID.
         */
        public function decoderAction($eid)
        {
                //
                // Sanitize:
                // 
                if (!($eid = $this->filter->sanitize($eid, "int"))) {
                        throw new Exception("Missing or invalid exam ID", Error::PRECONDITION_FAILED);
                }

                // 
                // Check route access:
                // 
                $this->checkAccess(array(
                        'eid' => $eid
                ));

                // 
                // Load exam data:
                // 
                if (!($exam = Exam::findFirst($eid))) {
                        throw new Exception("Failed fetch exam model", Error::BAD_REQUEST);
                }

                // 
                // Set data for view:
                // 
                $this->view->setVars(array(
                        'exam'    => $exam,
                        'contact' => $this->config->contact->toArray()
                ));
        }

        /**
         * Show student view for result download.
         * @param int $eid The exam ID.
         */
        public function studentAction($eid)
        {
                //
                // Sanitize:
                // 
                if (!($eid = $this->filter->sanitize($eid, "int"))) {
                        throw new Exception("Missing or invalid exam ID", Error::PRECONDITION_FAILED);
                }
                if (!($user = $this->request->get("user", "string"))) {
                        $user = $this->user->getPrincipalName();
                }

                // 
                // Check route access:
                // 
                if ($this->config->result->public == false) {
                        $this->checkAccess(array(
                                'eid' => $eid
                        ));
                }

                // 
                // Load exam data:
                // 
                if (!($exam = Exam::findFirst($eid))) {
                        throw new Exception("Failed fetch exam model", Error::BAD_REQUEST);
                }

                // 
                // Set data for view:
                // 
                $this->view->setVars(array(
                        'exam'    => $exam,
                        'user'    => $user,
                        'contact' => $this->config->contact->toArray()
                ));
        }

        /**
         * Find render job action.
         * @throws Exception
         */
        public function findAction()
        {
                // 
                // This action has no GUI:
                // 
                $this->view->disable();

                // 
                // Sanitize:
                // 
                if (!($eid = $this->request->get("exam_id", "int"))) {
                        throw new Exception("Missing or invalid exam ID", Error::PRECONDITION_FAILED);
                }
                if (!($type = $this->request->get("type", "string"))) {
                        throw new Exception("Missing or invalid type", Error::PRECONDITION_FAILED);
                }
                if (!($user = $this->request->get("user", "string"))) {
                        $user = $this->user->getPrincipalName();
                }

                // 
                // Check route access:
                // 
                $this->checkAccess(array(
                        'eid' => $eid
                ));

                // 
                // Find job in queue:
                // 
                $queue = new RenderQueue();
                $model = $queue->findJob($eid, $type, $user);

                // 
                // Set job position:
                // 
                $model->position = $queue->getPosition($model->id);

                // 
                // Send JSON reponse:
                // 
                $this->sendResponse('find', $model);
        }

        /**
         * Add render job action.
         * @throws Exception
         */
        public function addAction()
        {
                // 
                // This action has no GUI:
                // 
                $this->view->disable();

                // 
                // Sanitize:
                // 
                if (!($eid = $this->request->get("exam_id", "int"))) {
                        throw new Exception("Missing or invalid exam ID", Error::PRECONDITION_FAILED);
                }
                if (!($type = $this->request->get("type", "string"))) {
                        throw new Exception("Missing or invalid type", Error::PRECONDITION_FAILED);
                }
                if (!($user = $this->request->get("user", "string"))) {
                        $user = $this->user->getPrincipalName();
                }

                // 
                // Check route access:
                // 
                $this->checkAccess(array(
                        'eid' => $eid
                ));

                // 
                // Assign all request parameters:
                // 
                $model = new Render();
                $model->assign($this->request->get());

                // 
                // Set user in render:
                // 
                $model->user = $user;

                // 
                // Result requires the student model:
                // 
                if ($model->type == Render::TYPE_RESULT) {
                        $student = RenderQueue::getStudent($user, $eid);
                } else {
                        $student = null;
                }

                // 
                // Queue render job:
                // 
                $queue = new RenderQueue();
                $queue->addJob($model, $student);

                // 
                // Set job position:
                // 
                $model->position = $queue->getPosition($model->id);

                // 
                // Send JSON reponse:
                // 
                $this->sendResponse('add', $model);
        }

        /**
         * Cancel render job action.
         * @throws Exception
         */
        public function cancelAction()
        {
                // 
                // This action has no GUI:
                // 
                $this->view->disable();

                // 
                // Sanitize:
                // 
                if (!($jid = $this->request->get("id", "int"))) {
                        throw new Exception("Missing or invalid job ID", Error::PRECONDITION_FAILED);
                }

                // 
                // Check route access:
                // 
                $this->checkAccess();

                // 
                // Queue render job:
                // 
                $queue = new RenderQueue();
                $result = $queue->cancelJob($jid);

                // 
                // Send JSON reponse:
                // 
                $this->sendResponse('cancel', array(
                        'status' => 'cancelled',
                        'result' => $result
                ));
        }

        /**
         * Status of render job.
         * @throws Exception
         */
        public function statusAction()
        {
                // 
                // This action has no GUI:
                // 
                $this->view->disable();

                // 
                // Sanitize:
                // 
                if (!($jid = $this->request->get("id", "int"))) {
                        throw new Exception("Missing or invalid job ID", Error::PRECONDITION_FAILED);
                }

                // 
                // Check route access:
                // 
                $this->checkAccess();

                // 
                // Queue render job:
                // 
                $queue = new RenderQueue();
                $model = $queue->getStatus($jid);

                // 
                // Set job position:
                // 
                $model->position = $queue->getPosition($model->id);

                // 
                // Send JSON reponse:
                // 
                $this->sendResponse('status', $model);
        }

        /**
         * Result of render job.
         * @throws Exception
         */
        public function resultAction()
        {
                // 
                // This action has no GUI:
                // 
                $this->view->disable();

                // 
                // Sanitize:
                // 
                if (!($jid = $this->request->get("id", "int"))) {
                        throw new Exception("Missing or invalid job ID", Error::PRECONDITION_FAILED);
                }

                // 
                // Check route access:
                // 
                $this->checkAccess();

                // 
                // Queue render job:
                // 
                $queue = new RenderQueue();
                $result = $queue->getResult($jid);

                // 
                // Send JSON reponse:
                // 
                $this->sendResponse('result', $result);
        }

        /**
         * Update rendered job.
         * @throws Exception
         */
        public function refreshAction()
        {
                // 
                // This action has no GUI:
                // 
                $this->view->disable();

                // 
                // Sanitize:
                // 
                if (!($eid = $this->request->get("exam_id", "int"))) {
                        throw new Exception("Missing or invalid exam ID", Error::PRECONDITION_FAILED);
                }
                if (!($type = $this->request->get("type", "string"))) {
                        throw new Exception("Missing or invalid type", Error::PRECONDITION_FAILED);
                }
                if (!($user = $this->request->get("user", "string"))) {
                        $user = $this->user->getPrincipalName();
                }

                // 
                // Check route access:
                // 
                $this->checkAccess(array(
                        'eid' => $eid
                ));

                // 
                // Find job in queue:
                // 
                $queue = new RenderQueue();
                $model = $queue->updateJob($eid, $type, $user);

                // 
                // Set job position:
                // 
                $model->position = $queue->getPosition($model->id);

                // 
                // Send JSON reponse:
                // 
                $this->sendResponse('refresh', $model);
        }

        /**
         * Handle download of rendered files.
         * @throws Exception
         */
        public function downloadAction()
        {
                // 
                // This action has no GUI:
                // 
                $this->view->disable();

                // 
                // Sanitize:
                // 
                if (!($jid = $this->request->get("id", "int"))) {
                        throw new Exception("Missing or invalid job ID", Error::PRECONDITION_FAILED);
                }
                if (!($eid = $this->request->get("exam_id", "int"))) {
                        throw new Exception("Missing or invalid exam ID", Error::PRECONDITION_FAILED);
                }
                if (!($type = $this->request->get("type", "string"))) {
                        throw new Exception("Missing or invalid type", Error::PRECONDITION_FAILED);
                }
                if (!($user = $this->request->get("user", "string"))) {
                        $user = $this->user->getPrincipalName();
                }

                // 
                // Check route access:
                // 
                $this->checkAccess(array());

                // 
                // Find job in queue:
                // 
                $queue = new RenderQueue();
                $model = $queue->findJob($eid, $type, $user);

                // 
                // The render job should have finished status:
                // 
                if ($model->status != Render::STATUS_FINISH) {
                        throw new Exception("The render job has not finished yet", Error::PRECONDITION_FAILED);
                }

                // 
                // Students can't download results while having an active exam:
                // 
                if ($this->user->getPrimaryRole() == Roles::STUDENT) {
                        $queue->canAccess($model);
                }

                // 
                // Send PDF file:
                // 
                $source = sprintf("%s/%s", $this->config->application->cacheDir, $model->path);
                $target = sprintf("%s-%d-%s.pdf", $model->type, $model->exam_id, $model->user);

                $this->response->setFileToSend($source, $target);
                $this->response->setContentType('application/pdf', 'UTF-8');

                $this->response->send();
        }

        /**
         * Send JSON encoded response.
         * 
         * @param string $action The source action.
         * @param mixed $result The response data.
         */
        private function sendResponse($action, $result)
        {
                // 
                // Strip URL as it contains render token:
                // 
                if (is_object($result)) {
                        if ($result->type == Render::TYPE_ARCHIVE ||
                            $result->type == Render::TYPE_RESULT) {
                                $result->url = null;
                        }
                }

                // 
                // Send JSON response:
                // 
                $this->response->setJsonContent(array(
                        'action' => $action,
                        'result' => $result
                ));
                $this->response->send();
        }

        /**
         * Get render object.
         * 
         * @param string $type The render type (pdf or image).
         * @param string $file The default filename.
         * @return Renderer
         * @throws Exception
         */
        private function getRender($type, $file)
        {
                // 
                // Create render object:
                // 
                $render = $this->render->getRender($type);

                // 
                // Set request parameters:
                // 
                $render->url = $this->request->get('url', 'string');
                $render->filename = $this->request->get('filename', 'string', $file);

                // 
                // Check required parameters:
                // 
                if (!isset($render->url)) {
                        throw new Exception("Missing required url parameter");
                }

                // 
                // Generate full domain link if local:
                // 
                if ($render->url[0] == '/') {
                        $this->url->setBaseUri(
                            sprintf("%s://%s/%s/", $this->request->getScheme(), $this->request->getServerName(), trim($this->config->application->baseUri, '/'))
                        );
                        $render->url = $this->url->get($render->url);
                        if (strstr($render->url, '?')) {
                                $render->url .= '&token=' . file_get_contents($this->config->render->token);
                                $render->url .= '&user=' . $this->user->getPrincipalName();
                        } else {
                                $render->url .= '?token=' . file_get_contents($this->config->render->token);
                                $render->url .= '&user=' . $this->user->getPrincipalName();
                        }
                }

                return $render;
        }

}
