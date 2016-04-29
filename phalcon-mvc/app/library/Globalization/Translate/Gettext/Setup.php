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
use OpenExam\Library\Globalization\Translate\Setup as SetupInterface;
use Phalcon\Mvc\User\Component;

/**
 * Translation setup class for GNU gettext.
 * 
 * @author Anders Lövgren (Computing Department at BMC, Uppsala University)
 */
class Setup extends Component implements SetupInterface
{

        /**
         * Runtime options.
         * @var array 
         */
        private $_options;
        private $_consumer;

        /**
         * Constructor.
         * @param array $options Runtime options.
         */
        public function __construct($consumer, $options)
        {
                if (!extension_loaded('gettext')) {
                        throw new Exception('The gettext extension is not loaded.');
                }

                $this->_consumer = $consumer;
                $this->_options = $options;
        }

        /**
         * Set text domain for next call.
         * @param string $domain The text domain.
         */
        public function setDomain($domain)
        {
                $this->_options['module'] = $domain;
        }

        public function clean()
        {
                $command = new Command\Clean($this->_consumer, $this->_options);
                $command->process();
        }

        public function compile()
        {
                $command = new Command\Compile($this->_consumer, $this->_options);
                $command->process();
        }

        public function initialize()
        {
                $command = new Command\Initialize($this->_consumer, $this->_options);
                $command->process();
        }

        public function merge()
        {
                $command = new Command\Merge($this->_consumer, $this->_options);
                $command->process();
        }

        public function update()
        {
                $command = new Command\Update($this->_consumer, $this->_options);
                $command->process();
        }

}
