<?php

// 
// The source code is copyrighted, with equal shared rights, between the
// authors (see the file AUTHORS) and the OpenExam project, Uppsala University 
// unless otherwise explicit stated elsewhere.
// 
// File:    DeferredAdapter.php
// Created: 2017-01-16 04:05:44
// 
// Author:  Anders Lövgren (QNET/BMC CompDept)
// 

namespace OpenExam\Library\Database\Adapter\Deferred;

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
                                } catch (\Exception $exception) {
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
