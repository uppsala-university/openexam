<?php

/*
 * Copyright (C) 2014-2018 The OpenExam Project
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
 */

// 
// File:    RenderService.php
// Created: 2014-10-27 23:27:25
// 
// Author:  Anders Lövgren (QNET/BMC CompDept)
// 

namespace OpenExam\Library\Render;

use OpenExam\Library\Render\Command\RenderImage as RenderImageCommand;
use OpenExam\Library\Render\Command\RenderPdfDocument as RenderPdfDocumentCommand;
use OpenExam\Library\Render\Extension\RenderImage as RenderImageExtension;
use OpenExam\Library\Render\Extension\RenderPdfDocument as RenderPdfDocumentExtension;
use Phalcon\Mvc\User\Component;

/**
 * The render service.
 * @author Anders Lövgren (QNET/BMC CompDept)
 */
class RenderService extends Component
{

        /**
         * The registered render objects.
         * @var Renderer[] 
         */
        private $_render = array();
        private $_method;

        /**
         * Constructor.
         * @param string $method The render method (command or extension).
         */
        public function __construct($method = null)
        {
                if (isset($method)) {
                        $this->_method = $method;
                } else {
                        $this->_method = $this->config->render->method;
                }
        }

        /**
         * Set render method.
         * @param string $method The render method (command or extension).
         */
        public function setMethod($method)
        {
                foreach ($this->_render as $render) {
                        unset($render);
                }

                $this->_render = array();
                $this->_method = $method;
        }

        /**
         * Get render method.
         * @return string
         */
        public function getMethod()
        {
                return $this->_method;
        }

        /**
         * Get render object for type.
         * @param string $type The render type (Renderer::FORMAT_XXX).
         * @return Renderer
         */
        public function getRender($type)
        {
                if (isset($this->_render[$type])) {
                        return $this->_render[$type];
                } else {
                        $this->_render[$type] = $this->createRender($type);
                        return $this->_render[$type];
                }
        }

        /**
         * Set render for type.
         * @param string $type The render type (Renderer::FORMAT_XXX).
         * @param Renderer $render The render object.
         */
        public function setRender($type, $render)
        {
                $this->_render[$type] = $render;
        }

        /**
         * Create render object for type.
         * @param string $type The render type (Renderer::FORMAT_XXX).
         * @throws Exception
         */
        private function createRender($type)
        {
                if ($type == Renderer::FORMAT_IMAGE) {
                        if ($this->config->render->method == 'command') {
                                return new RenderImageCommand(
                                    $this->config->render->command->image
                                );
                        } else {
                                return new RenderImageExtension(
                                    $this->config->render->extension->image
                                );
                        }
                } elseif ($type == Renderer::FORMAT_PDF) {
                        if ($this->config->render->method == 'command') {
                                return new RenderPdfDocumentCommand(
                                    $this->config->render->command->pdf
                                );
                        } else {
                                return new RenderPdfDocumentExtension(
                                    $this->config->render->extension->pdf
                                );
                        }
                } else {
                        throw new Exception($this->tr->_("Unknown format type %format%", array('format' => $type)));
                }
        }

}
