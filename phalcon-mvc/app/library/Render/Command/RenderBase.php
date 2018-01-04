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
// File:    RenderBase.php
// Created: 2017-10-23 19:42:31
// 
// Author:  Anders Lövgren (Computing Department at BMC, Uppsala University)
// 

namespace OpenExam\Library\Render\Command;

use OpenExam\Library\Console\Command;

/**
 * Base class for page render implementations (command line based).
 * 
 * <code>
 * // 
 * // Example on using the generic render method:
 * // 
 * $render->render(
 *      'file.pdf', 
 *      array('quality' => 95, 'greyscale' => ''),
 *      array('in' => ... pages)
 * );
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
         * The command.
         * @var string 
         */
        private $_command;
        /**
         * The global options.
         * @var array 
         */
        protected $_globals = array();

        /**
         * Constructor.
         * @param string $command The command string.
         */
        public function __construct($command)
        {
                $this->_command = $command;
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
         * Render image or PDF.
         * 
         * @param string $filename The suggested filename.
         * @param array $settings The image settings.
         * @return bool
         */
        public function render($filename, $settings)
        {
                $options = $this->getOptions();

                $execute = sprintf("%s %s %s %s", $this->_command, $options, $settings['in'], $filename);

                $command = new Command($execute);
                $command->setBuffered(false);

                return $command->execute();
        }

        /**
         * Get command line options.
         * @return string 
         */
        private function getOptions()
        {
                $options = array();

                foreach ($this->_globals as $key => $val) {
                        if (is_bool($val) && $val == true) {
                                $val = "";
                        }
                        if (strlen($key) == 1) {
                                $options[] = "-$key $val";
                        } else {
                                $options[] = "--$key $val";
                        }
                }

                return implode(" ", $options);
        }

}
