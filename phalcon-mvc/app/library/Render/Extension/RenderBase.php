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
// File:    RenderBase.php
// Created: 2014-10-27 22:50:22
// 
// Author:  Anders Lövgren (QNET/BMC CompDept)
// 

namespace OpenExam\Library\Render\Extension;

use OpenExam\Library\Render\Exception;

/**
 * Base class for page render implementations (extension based).
 * 
 * <code>
 * // 
 * // Example on using the generic render method:
 * // 
 * $render->render(
 *      // Output format:
 *      'pdf', 
 *      // Global settings:
 *      array('out' => '/tmp/output.pdf', 'imageQuality' => '95'),
 *      // Object settings:
 *      array(
 *              array('page' => 'http://www.yahoo.com/'),
 *              array('page' => 'http://www.google.com/')
 *      )
 * );
 * </code>
 * 
 * @author Anders Lövgren (QNET/BMC CompDept)
 */
abstract class RenderBase
{

        /**
         * Global settings.
         * @var array 
         */
        private $_globals;

        /**
         * Constructor.
         * @param array $globals Global settings.
         */
        public function __construct($globals = array())
        {
                if (!extension_loaded('phpwkhtmltox')) {
                        throw new Exception('Extension phpwkhtmltox is not loaded.');
                }
                $this->_globals = (array) $globals;
        }

        /**
         * Set global options.
         * @param array $globals Global settings.
         */
        public function setOptions($globals)
        {
                $this->_globals = array_merge($this->_globals, (array) $globals);
        }

        /**
         * Add global option.
         * @param string $name Option name.
         * @param string $value Option value.
         */
        public function addOption($name, $value)
        {
                $this->_globals[$name] = $value;
        }

        /**
         * Generic render method.
         * @param string $type The rendition type (image or pdf).
         * @param array $globals Global settings.
         * @param array $objects Object settings (only for PDF).
         * @return bool
         */
        public function render($type, $globals, $objects = array())
        {

                $globals = array_merge((array) $globals, $this->_globals);

                // 
                // Render image fail unless the output type is known:
                // 
                if (isset($globals['fmt'])) {
                        $extension = $globals['fmt'];
                } elseif ($globals['out'] != '-') {
                        $extension = pathinfo($globals['out'], PATHINFO_EXTENSION);
                } elseif ($type == 'image') {
                        $extension = 'png';
                } elseif ($type == 'pdf') {
                        $extension = 'pdf';
                }

                // 
                // Emulate '-' output option:
                // 
                if ($globals['out'] == '-') {
                        $tmpfile = tempnam(sys_get_temp_dir(), 'result-wktohtml');
                        $globals['out'] = $tmpfile;
                }

                $result = wkhtmltox_convert($type, $globals, $objects);

                if (isset($tmpfile) && file_exists($tmpfile)) {
                        echo file_get_contents($tmpfile);
                        unlink($tmpfile);
                }

                return $result;
        }

}
