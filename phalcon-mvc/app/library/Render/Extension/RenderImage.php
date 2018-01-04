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
// File:    RenderImage.php
// Created: 2014-10-27 21:42:58
// 
// Author:  Anders Lövgren (QNET/BMC CompDept)
// 

namespace OpenExam\Library\Render\Extension;

use OpenExam\Library\Render\Renderer;

/**
 * Image render class.
 * 
 * This class renders and URL or local HTML file as an image using the PHP 
 * wrapper extension (phpwkhtmltox) for libwkhtmltox.
 * 
 * <code>
 * $settings = array(
 *      'in'  => 'http://www.google.com/'
 * );
 * 
 * // 
 * // Save JPEG image to file:
 * // 
 * $render = new RenderImage();
 * $render->save('/tmp/output.jpeg', $settings);
 * 
 * // 
 * // Send JPEG image to stdout:
 * // 
 * $render = new RenderImage();
 * $render->send('file.jpeg', $settings);
 * </code>
 *
 * @author Anders Lövgren (QNET/BMC CompDept)
 * @see http://wkhtmltopdf.org/libwkhtmltox/pagesettings.html
 */
class RenderImage extends RenderBase implements Renderer
{

        private static $_mime = array(
                'png' => 'image/png',
                'jpg' => 'image/jpeg',
                'bmp' => 'image/bmp',
                'svg' => 'image/svg+xml'
        );

        /**
         * Save rendered image.
         * 
         * @param string $filename The output file.
         * @param array $settings The image settings.
         * @return bool
         */
        public function save($filename, $settings)
        {
                return parent::render('image', array_merge((array) $settings, array('out' => $filename)));
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
                        header(sprintf('Content-type: %s', self::$_mime[$settings['fmt']]));
                        header(sprintf("Content-Disposition: attachment; filename=\"%s\"", $filename));
                }
                return parent::render('image', array_merge((array) $settings, array('out' => '-')));
        }

}
