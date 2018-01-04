<?php

/*
 * Copyright (C) 2016-2018 The OpenExam Project
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
// File:    AuditConfig.php
// Created: 2016-04-21 03:50:32
// 
// Author:  Anders Lövgren (QNET/BMC CompDept)
// 

namespace OpenExam\Library\Model\Audit\Config;

use OpenExam\Library\Model\Audit\Audit;

/**
 * Model audit config.
 * 
 * This class represents the audit configuration for a single model.
 *
 * @author Anders Lövgren (QNET/BMC CompDept)
 */
class AuditConfig
{

        /**
         * Audit configuration.
         * @var array 
         */
        private $_config;

        /**
         * Constructor.
         * @param array $config The audit config.
         */
        public function __construct($config)
        {
                $this->_config = $config;

                if (!isset($this->_config['actions'])) {
                        $this->_config['actions'] = Audit::getDefaultActions();
                }
        }

        /**
         * Get all actions.
         * @return array
         */
        public function getActions()
        {
                return $this->_config['actions'];
        }

        /**
         * Check if action is defined.
         * @param string $action The action name (e.g. 'update').
         * @return boolean
         */
        public function hasAction($action)
        {
                return in_array($action, $this->_config['actions']);
        }

        /**
         * Get this object configuration.
         * @return array
         */
        public function getConfig()
        {
                return $this->_config;
        }

        /**
         * Get all targets (e.g. array('file','data')).
         * @return array
         */
        public function getTargets()
        {
                return array_filter(array_keys($this->_config), function($value) {
                        return $value != 'actions';
                });
        }

        /**
         * Get target config.
         * @param string $target The target name (e.g. 'file' or 'data').
         * @return boolean|array
         */
        public function getTarget($target)
        {
                if (!isset($this->_config[$target])) {
                        return false;
                }
                return $this->_config[$target];
        }

        /**
         * Check if target is defined.
         * @param string $target The target name (e.g. 'file' or 'data').
         * @return boolean
         */
        public function hasTarget($target)
        {
                return isset($this->_config[$target]);
        }

}
