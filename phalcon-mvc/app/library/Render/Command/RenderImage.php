<?php

/*
 * Copyright (C) 2017-2018 The OpenExam Project
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
// File:    RenderImage.php
// Created: 2017-01-31 02:21:27
// 
// Author:  Anders Lövgren (Computing Department at BMC, Uppsala University)
// 

namespace OpenExam\Library\Render\Command;

use OpenExam\Library\Render\Renderer;

/**
 * Image render class.
 * 
 * This class renders and URL or local HTML file as an image using the  
 * wkhtmltopdf command line tools (wkhtmltoimage).
 * 
 * <code>
 * // 
 * // The input URL:
 * // 
 * $input = array(
 *      'in' => 'http://www.google.se'
 * );
 * 
 * // 
 * // The command to execute:
 * // 
 * $command = "wkhtmltoimage --format png --quality 95";
 * 
 * // 
 * // Save image to file:
 * // 
 * $render = new RenderImage($command);
 * $render->save('output.png', $input);
 * 
 * // 
 * // Send image to stdout:
 * // 
 * $render = new RenderImage($command);
 * $render->send('output.png', $input, true);
 * </code>
 *
 * Global options (command line options) can also be set by calling
 * the setOptions() method:
 * 
 * <code>
 * $render = new RenderImage("wkhtmltoimage");
 * $render->setOptions(array(
 *      'format'  => 'png',
 *      'quality' => 95
 * ));
 * $render->save('output.png', $input);
 * </code>
 * 
 * @author Anders Lövgren (Computing Department at BMC, Uppsala University)
 */
class RenderImage extends RenderBase implements Renderer
{

        /**
         * The MIME type map.
         * @var array 
         */
        private static $_mime = array(
                'png' => 'image/png',
                'jpg' => 'image/jpeg',
                'bmp' => 'image/bmp',
                'svg' => 'image/svg+xml'
        );

        /**
         * Constructor.
         * @param string $command The command string.
         */
        public function __construct($command)
        {
                parent::__construct($command);
                parent::addOption('format', 'png');
        }

        /**
         * Set the image format.
         * @param string $format Image format.
         */
        public function setFormat($format)
        {
                parent::addOption('format', $format);
        }

        /**
         * Save rendered image.
         * 
         * @param string $filename The output file.
         * @param array $settings The image settings.
         * @return bool
         */
        public function save($filename, $settings)
        {
                return parent::render($filename, $settings);
        }

        /**
         * Send rendered image.
         * 
         * @param string $filename The suggested filename.
         * @param array $settings The image settings.
         * @param bool $headers Output HTTP headers.
         * @return bool
         */
        public function send($filename, $settings, $headers = true)
        {
                if ($headers) {
                        header(sprintf('Content-type: %s', self::$_mime[$this->_globals['format']]));
                        header(sprintf("Content-Disposition: attachment; filename=\"%s\"", $filename));
                }
                return parent::render("-", $settings);
        }

}
