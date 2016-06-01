<?php

// 
// The source code is copyrighted, with equal shared rights, between the
// authors (see the file AUTHORS) and the OpenExam project, Uppsala University 
// unless otherwise explicit stated elsewhere.
// 
// File:    RenderService.php
// Created: 2014-10-27 23:27:25
// 
// Author:  Anders Lövgren (QNET/BMC CompDept)
// 

namespace OpenExam\Library\Render;

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

        /**
         * Get render object for type.
         * @param string $type The render type (Renderer::FORMAT_XXX).
         * @return Renderer
         */
        public function getRender($type)
        {
                if (!isset($this->_render[$type])) {
                        $this->createRender($type);
                }
                return $this->_render[$type];
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
                switch ($type) {
                        case Renderer::FORMAT_IMAGE:
                                $this->_render[$type] = new RenderImage($this->config->render->image);
                                break;
                        case Renderer::FORMAT_PDF:
                                $this->_render[$type] = new RenderPdfDocument($this->config->render->pdf);
                                break;
                        default:
                                throw new Exception($this->tr->_("Unknown format type %format%", array('format' => $type)));
                }
        }

}
