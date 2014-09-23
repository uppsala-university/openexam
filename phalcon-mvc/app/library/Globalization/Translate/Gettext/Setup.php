<?php

// 
// The source code is copyrighted, with equal shared rights, between the
// authors (see the file AUTHORS) and the OpenExam project, Uppsala University 
// unless otherwise explicit stated elsewhere.
// 
// File:    Setup.php
// Created: 2014-09-19 12:21:24
// 
// Author:  Anders Lövgren (Computing Department at BMC, Uppsala University)
// 

namespace OpenExam\Library\Globalization\Translate\Gettext;

use OpenExam\Library\Globalization\Exception;
use OpenExam\Library\Globalization\Translate;
use Phalcon\Mvc\User\Component;

/**
 * Translation setup class for GNU gettext.
 * 
 * @author Anders Lövgren (Computing Department at BMC, Uppsala University)
 */
class Setup extends Component implements Translate\Setup
{

        /**
         * Runtime options.
         * @var array 
         */
        private $options;
        private $consumer;

        /**
         * Constructor.
         * @param array $options Runtime options.
         */
        public function __construct($consumer, $options)
        {
                if (!extension_loaded('gettext')) {
                        throw new Exception('The gettext extension is not loaded.');
                }

                $this->consumer = $consumer;
                $this->options = $options;
        }

        /**
         * Set text domain for next call.
         * @param string $domain The text domain.
         */
        public function setDomain($domain)
        {
                $this->options['module'] = $domain;
        }

        public function clean()
        {
                $command = new Command\Clean($this->consumer, $this->options);
                $command->process();
        }

        public function compile()
        {
                $command = new Command\Compile($this->consumer, $this->options);
                $command->process();
        }

        public function initialize()
        {
                $command = new Command\Initialize($this->consumer, $this->options);
                $command->process();
        }

        public function merge()
        {
                $command = new Command\Merge($this->consumer, $this->options);
                $command->process();
        }

        public function update()
        {
                $command = new Command\Update($this->consumer, $this->options);
                $command->process();
        }

}
