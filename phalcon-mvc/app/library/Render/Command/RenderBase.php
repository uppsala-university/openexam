<?php

// 
// The source code is copyrighted, with equal shared rights, between the
// authors (see the file AUTHORS) and the OpenExam project, Uppsala University 
// unless otherwise explicit stated elsewhere.
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
                                $options[] = "--$key ";
                        } else {
                                $options[] = "--$key $val ";
                        }
                }

                return implode(" ", $options);
        }

}
