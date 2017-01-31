<?php

// 
// The source code is copyrighted, with equal shared rights, between the
// authors (see the file AUTHORS) and the OpenExam project, Uppsala University 
// unless otherwise explicit stated elsewhere.
// 
// File:    Renderer.php
// Created: 2014-10-27 21:34:30
// 
// Author:  Anders Lövgren (QNET/BMC CompDept)
// 

namespace OpenExam\Library\Render;

/**
 * Page render interface.
 * @author Anders Lövgren (QNET/BMC CompDept)
 */
interface Renderer
{

        /**
         * Render image.
         */
        const FORMAT_IMAGE = 'image';
        /**
         * Render PDF document.
         */
        const FORMAT_PDF = 'pdf';

        /**
         * Save rendered page(s) to file.
         * @param string $filename The output file.
         * @param array $settings The render settings or objects.
         */
        function save($filename, $settings);

        /**
         * Send rendered page(s) to stdout.
         * @param string $filename The suggested filename.
         * @param array $settings The render settings or objects.
         * @param bool $headers Output HTTP headers.
         */
        function send($filename, $settings, $headers = true);
}
