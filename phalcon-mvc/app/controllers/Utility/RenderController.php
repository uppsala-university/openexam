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
use OpenExam\Library\Render\Exception;
use OpenExam\Library\Render\Renderer;

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
        public function pngAction()
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
        public function jpgAction()
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
        public function bmpAction()
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
        public function svgAction()
        {
                $this->checkAccess();                
                $render = $this->getRender('image', 'file.svg');
                $render->setFormat('svg');
                $render->send($render->filename, array('in' => $render->url));
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
