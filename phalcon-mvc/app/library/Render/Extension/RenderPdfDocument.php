<?php

// 
// The source code is copyrighted, with equal shared rights, between the
// authors (see the file AUTHORS) and the OpenExam project, Uppsala University 
// unless otherwise explicit stated elsewhere.
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
