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
// File:    DeferredAdapter.php
// Created: 2017-01-16 04:05:44
// 
// Author:  Anders Lövgren (QNET/BMC CompDept)
// 

namespace OpenExam\Library\Database\Adapter\Deferred;

use Exception;
use OpenExam\Library\Database\Adapter\Factory\AdapterFactory;
use Phalcon\Config;
use Phalcon\Db\AdapterInterface;

/**
 * Abstract base class for deferred database adapters.
 *
 * @author Anders Lövgren (QNET/BMC CompDept)
 */
abstract class DeferredAdapter implements AdapterFactory
{

        /**
         * The adapter config.
         * @var array 
         */
        private $_config;
        /**
         * The connection parameters.
         * @var Config 
         */
        private $_params;

        /**
         * Constructor.
         * @param array $config The adapter options.
         * @param Config $params The connection parameters.
         */
        public function __construct($config, $params)
        {
                $this->_config = $config;
                $this->_params = $params;
        }

        public function __call($name, $arguments)
        {
                return call_user_func_array(array($this->_adapter, $name), $arguments);
        }

        public function __get($name)
        {
                if ($name == '_adapter') {
                        while (true) {
                                try {
                                        $this->_adapter = $this->createAdapter($this->_config);
                                        return $this->_adapter;
                                } catch (Exception $exception) {
                                        if (--$this->_params->retry < 0) {
                                                throw $exception;
                                        } else {
                                                sleep($this->_params->sleep);
                                        }
                                }
                        }
                }
        }

        /**
         * Get PDO database adapter.
         * @return AdapterInterface
         */
        public function getAdapter()
        {
                return $this->_adapter;
        }

}
