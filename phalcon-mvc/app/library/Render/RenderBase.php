<?php

// 
// The source code is copyrighted, with equal shared rights, between the
// authors (see the file AUTHORS) and the OpenExam project, Uppsala University 
// unless otherwise explicit stated elsewhere.
// 
// File:    RenderBase.php
// Created: 2014-10-27 22:50:22
// 
// Author:  Anders Lövgren (QNET/BMC CompDept)
// 

namespace OpenExam\Library\Render;

/**
 * Base class for page render implementations.
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
        protected $globals;

        /**
         * Constructor.
         * @param array $globals Global settings.
         */
        public function __construct($globals = array())
        {
                if (!extension_loaded('phpwkhtmltox')) {
                        throw new Exception('Extension phpwkhtmltox is not loaded.');
                }
                $this->globals = (array) $globals;
        }

        /**
         * Set global options.
         * @param array $globals Global settings.
         */
        public function setOptions($globals)
        {
                $this->globals = array_merge($this->globals, $globals);
        }

        /**
         * Add global option.
         * @param string $name Option name.
         * @param string $value Option value.
         */
        public function addOption($name, $value)
        {
                $this->globals[$name] = $value;
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
                $globals = array_merge($globals, $this->globals);

                // 
                // Emulate '-' output option:
                // 
                $tmpfile = tempnam(sys_get_temp_dir(), 'wktohtml');
                $dstfile = $globals['out'];
                $globals['out'] = $tmpfile;

                $result = wkhtmltox_convert($type, $globals, $objects);

                if ($dstfile == '-') {
                        echo file_get_contents($tmpfile);
                        unlink($tmpfile);
                } else {
                        rename($tmpfile, $dstfile);
                }

                return $result;
        }

}
