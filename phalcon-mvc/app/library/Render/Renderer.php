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
