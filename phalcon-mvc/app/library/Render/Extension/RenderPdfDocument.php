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
// File:    RenderPdfDocument.php
// Created: 2014-10-27 21:43:16
// 
// Author:  Anders Lövgren (QNET/BMC CompDept)
// 

namespace OpenExam\Library\Render\Extension;

use OpenExam\Library\Render\Renderer;

/**
 * PDF document render class.
 * 
 * This class renders and URL or local HTML file as an PDF file using the PHP 
 * wrapper extension (phpwkhtmltox) for libwkhtmltox.
 * 
 * <code>
 * $pages = array(
 *      array('page'  => 'http://www.google.com/'),
 *      array('page'  => 'https://se.yahoo.com')
 * );
 * 
 * // 
 * // Save PDF document to file:
 * // 
 * $render = new RenderPdfDocument();
 * $render->save('/tmp/output.pdf', $pages);
 * 
 * // 
 * // Send PDF document to stdout:
 * // 
 * $render = new RenderPdfDocument();
 * $render->send('file.pdf', $pages);
 * </code>
 * 
 * @author Anders Lövgren (QNET/BMC CompDept)
 * @see http://wkhtmltopdf.org/libwkhtmltox/pagesettings.html
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
                return parent::render('pdf', array('out' => $filename), (array) $objects);
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
                        header(sprintf('Content-type: %s', 'application/pdf'));
                        header(sprintf("Content-Disposition: attachment; filename=\"%s\"", $filename));
                }
                return parent::render('pdf', array('out' => '-'), (array) $objects);
        }

}
