<?php

// 
// The source code is copyrighted, with equal shared rights, between the
// authors (see the file AUTHORS) and the OpenExam project, Uppsala University 
// unless otherwise explicit stated elsewhere.
// 
// File:    RenderImage.php
// Created: 2014-10-27 21:42:58
// 
// Author:  Anders Lövgren (QNET/BMC CompDept)
// 

namespace OpenExam\Library\Render;

/**
 * Image render class.
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

        private static $mime = array(
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
                return parent::render('image', array_merge($settings, array('out' => $filename)));
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
                        header(sprintf('Content-type: %s', self::$mime[$settings['fmt']]));
                        header(sprintf("Content-Disposition: attachment; filename=\"%s\"", $filename));
                }
                return parent::render('image', array_merge($settings, array('out' => '-')));
        }

}
