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
// File:    ServiceConfig.php
// Created: 2016-04-20 20:26:13
// 
// Author:  Anders Lövgren (QNET/BMC CompDept)
// 

namespace OpenExam\Library\Model\Audit\Config;

use OpenExam\Library\Model\Audit\Audit;
use Phalcon\Config as PhalconConfig;
use Phalcon\Mvc\User\Component;

/**
 * Audit service configuration.
 * 
 * Takes care of parsing the audit configuration:
 * <pre>
 *       'audit' => bool|array(
 *           '*'|<model> => bool|array(      // The model name or '*' for all models (i).
 *               'actions' => array(...)     // Optional, defaults to array('create','update','delete').
 *               'file' => bool|array(
 *                   'name'       => <path>  // Optional, defaults to audit/<model>.dat
 *                   'format'     => <type>  // Optional, defaults to 'serialize' (ii).
 *               ),
 *               'data' => bool|array(
 *                   'connection' => <name>  // Optional, defaults to 'dbaudit'.
 *                   'table'      => <name>  // Optional, defaults to <model> name (iii).
 *               )
 *           )
  Ä       );
 * </pre>
 * 
 * @author Anders Lövgren (QNET/BMC CompDept)
 */
class ServiceConfig extends Component
{

        /**
         * Audit configuration entry.
         * @var PhalconConfig
         */
        private $_audit;
        /**
         * Audit configuration cache.
         * @var array 
         */
        private $_cache = array();

        /**
         * Constructor.
         * @param PhalconConfig $audit Audit configuration.
         */
        public function __construct($audit = null)
        {
                if (is_array($audit)) {
                        $this->_audit = new PhalconConfig($audit);
                } else {
                        $this->_audit = $audit;
                }
                if (!isset($this->_audit)) {
                        $this->_audit = $this->config->get('audit', false);
                }
        }

        /**
         * Check if audit is enabled for this model.
         * 
         * @param string $model The resource name.
         * @return boolean
         */
        public function hasAudit($model)
        {
                if (!$this->hasConfig($model)) {
                        $this->setConfig($model);
                }
                return is_array($this->_cache[$model]);
        }

        /**
         * Get audit configuration for this model.
         * 
         * @param string $model The resource name.
         * @return array|boolean 
         */
        public function getConfig($model)
        {
                if (!$this->hasConfig($model)) {
                        $this->setConfig($model);
                }
                return $this->_cache[$model];
        }

        /**
         * Get actions defined for this model.
         * 
         * @param string $model The resource name.
         * @return boolean|array 
         */
        public function getActions($model)
        {
                if (!$this->hasConfig($model)) {
                        $this->setConfig($model);
                }
                if (!isset($this->_cache[$model])) {
                        return false;
                } elseif (!isset($this->_cache[$model]['actions'])) {
                        return Audit::getDefaultActions();
                } else {
                        return $this->_cache[$model]['actions'];
                }
        }

        /**
         * Check if action is defined for this model.
         * 
         * @param string $model The resource name.
         * @param string $action The action name (e.g. 'update').
         * @return boolean
         */
        public function hasAction($model, $action)
        {
                if (($actions = $this->getActions($model))) {
                        return in_array($action, $actions);
                } else {
                        return false;
                }
        }

        /**
         * Check if audit target (e.g. file) is defined.
         * 
         * @param string $model The resource name.
         * @param string $target The target identifier.
         * @return boolean
         */
        public function hasTarget($model, $target)
        {
                if (!$this->hasConfig($model)) {
                        $this->setConfig($model);
                }
                if (!isset($this->_cache[$model][$target])) {
                        return false;
                } else {
                        return $this->_cache[$model][$target] != false;
                }
        }

        /**
         * Get audit target. Return false if target is not defined.
         * 
         * @param string $model The resource name.
         * @param string $target The target identifier.
         * @return boolean|array
         */
        public function getTarget($model, $target)
        {
                if (!$this->hasConfig($model)) {
                        $this->setConfig($model);
                }
                if (!isset($this->_cache[$model][$target])) {
                        return false;
                } else {
                        return $this->_cache[$model][$target];
                }
        }

        /**
         * Disable audit for this model.
         * @param string $model The resource name.
         */
        public function setDisabled($model)
        {
                if (isset($this->_cache[$model])) {
                        $this->_cache[$model] = false;
                }
        }

        /**
         * Check if cached configuration exist for this model.
         * 
         * @param string $model
         * @return boolean
         */
        private function hasConfig($model)
        {
                return array_key_exists($model, $this->_cache);
        }

        /**
         * Detect and cache audit configuration for this model.
         * 
         * @param string $model The resource name.
         */
        private function setConfig($model)
        {
                $this->_cache[$model] = $this->getAudit($model);
        }

        /**
         * Get audit config from system configuration.
         * 
         * Returns either false (audit missing) or the configured array. If
         * configuration for this model is true, then the default configuration
         * is returned.
         * 
         * @param string $model The resource name.
         * @return boolean|array
         */
        private function getAudit($model)
        {
                if ($this->_audit == false) {
                        return false;
                }
                if (is_bool($this->_audit)) {
                        return self::getDefault($model);
                }
                foreach (array($model, '*') as $key) {
                        if (isset($this->_audit[$key])) {
                                $data = $this->_audit[$key];
                                break;
                        }
                }
                if (!isset($data) || $data == false) {
                        return false;
                }
                if (is_bool($data)) {
                        return self::getDefault($model);
                } else {
                        return $data->toArray();
                }
        }

        /**
         * Get default audit config.
         * @param string $model The resource name.
         * @return array
         */
        public static function getDefault($model)
        {
                return array(
                        'actions' => Audit::getDefaultActions(),
                        'data'    => array(
                                'connection' => 'dbaudit',
                                'table'      => $model
                ));
        }

}
