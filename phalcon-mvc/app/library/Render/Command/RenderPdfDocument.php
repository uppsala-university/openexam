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
// File:    RenderPdfDocument.php
// Created: 2017-01-31 02:46:45
// 
// Author:  Anders Lövgren (Computing Department at BMC, Uppsala University)
// 

namespace OpenExam\Library\Render\Command;

use OpenExam\Library\Render\Renderer;

/**
 * PDF document render class.
 *
 * This class renders and URL or local HTML file as an PDF file using the 
 * wkhtmltopdf command line tools (wkhtmltopdf).
 * 
 * <code>
 * // 
 * // The URL's to render:
 * // 
 * $pages = array(
 *      array('page'  => 'http://www.google.com/'),
 *      array('page'  => 'https://se.yahoo.com')
 * );
 * 
 * // 
 * // The command to execute:
 * // 
 * $command = "wkhtmltopdf --page-size a4 --grayscale --enable-javascript";
 * 
 * // 
 * // Save PDF document to file:
 * // 
 * $render = new RenderPdfDocument($command);
 * $render->save('output.pdf', $pages);
 * 
 * // 
 * // Send PDF document to stdout:
 * // 
 * $render = new RenderPdfDocument($command);
 * $render->send('output.pdf', $pages);
 * </code>
 * 
 * Global options (command line options) can also be set by calling
 * the setOptions() method:
 * 
 * <code>
 * $render = new RenderImage("wkhtmltopdf");
 * $render->setOptions(array(
 *      'page-size'         => 'a4',
 *      'grayscale'         => true,
 *      'enable-javascript' => true
 * ));
 * $render->save('output.png', $input);
 * </code>
 * 
 * @author Anders Lövgren (Computing Department at BMC, Uppsala University)
 */
class RenderPdfDocument extends RenderBase implements Renderer
{

        /**
         * Save rendered PDF document.
         * 
         * @param string $filename The output file.
         * @param array $objects PDF object settings.
         * @return bool
         */
        public function save($filename, $objects)
        {
                return parent::render($filename, self::getPages($objects));
        }

        /**
         * Send rendered PDF document.
         * 
         * @param string $filename The suggested filename.
         * @param array $objects PDF object settings.
         * @param bool $headers Output HTTP headers.
         * @return bool
         */
        public function send($filename, $objects, $headers = true)
        {
                if ($headers) {
                        header(sprintf('Content-Type: %s', 'application/pdf; UTF-8'));
                        header(sprintf("Content-Disposition: attachment; filename=\"%s\"", $filename));
                }

                return parent::render("-", self::getPages($objects));
        }

        /**
         * Extract pages from settings array.
         * @param array $objects The PDF objects.
         * @return array
         */
        private static function getPages($objects)
        {
                $pages = "";

                foreach ($objects as $obj) {
                        $pages .= " \"" . $obj['page'] . "\"";
                }

                return array('in' => $pages);
        }

}
