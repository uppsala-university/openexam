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
// File:    Setup.php
// Created: 2014-09-19 12:21:24
// 
// Author:  Anders Lövgren (Computing Department at BMC, Uppsala University)
// 

namespace OpenExam\Library\Globalization\Translate\Gettext;

use OpenExam\Library\Globalization\Exception;
use OpenExam\Library\Globalization\Translate\Gettext\Command\Clean as CleanCommand;
use OpenExam\Library\Globalization\Translate\Gettext\Command\Compile as CompileCommand;
use OpenExam\Library\Globalization\Translate\Gettext\Command\Initialize as InitializeCommand;
use OpenExam\Library\Globalization\Translate\Gettext\Command\Merge as MergeCommand;
use OpenExam\Library\Globalization\Translate\Gettext\Command\Update as UpdateCommand;
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
                $command = new CleanCommand($this->_consumer, $this->_options);
                $command->process();
        }

        public function compile()
        {
                $command = new CompileCommand($this->_consumer, $this->_options);
                $command->process();
        }

        public function initialize()
        {
                $command = new InitializeCommand($this->_consumer, $this->_options);
                $command->process();
        }

        public function merge()
        {
                $command = new MergeCommand($this->_consumer, $this->_options);
                $command->process();
        }

        public function update()
        {
                $command = new UpdateCommand($this->_consumer, $this->_options);
                $command->process();
        }

}
