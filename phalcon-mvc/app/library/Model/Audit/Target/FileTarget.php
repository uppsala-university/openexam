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
// File:    FileTarget.php
// Created: 2016-04-15 15:49:54
// 
// Author:  Anders Lövgren (Computing Department at BMC, Uppsala University)
// 

namespace OpenExam\Library\Model\Audit\Target;

use Phalcon\Mvc\ModelInterface;
use Phalcon\Mvc\User\Component;

/**
 * Audit trail for models (file storage).
 * 
 * To implement audit on a model:
 * 
 * 1. Enable keep snapshots in the audited model.
 * 2. Call addBehavior(new FileTargetAudit()) in model initialize.
 *
 * <code>
 * protected function initialize()
 * {
 *      $this->keepSnapshots(true);
 *      $this->addBehavior(new FileTargetAudit());
 * }
 * </code>
 * 
 * The output format and log location can be customized by passing an options
 * array to the contructor:
 * 
 * <code>
 * $this->addBehavior(new FileTargetAudit(array(
 *      'format' => Audit::FORMAT_SERIALIZE,
 *      'file'   => '/tmp/output.log'
 * )));
 * </code>
 * 
 * @author Anders Lövgren (Computing Department at BMC, Uppsala University)
 */
class FileTarget extends Component implements AuditTarget
{

        /**
         * Format output using var_export().
         */
        const FORMAT_EXPORT = 'export';
        /**
         * Format output as JSON encoded data.
         */
        const FORMAT_JSON = 'json';
        /**
         * Format output as PHP serialized data.
         */
        const FORMAT_SERIALIZE = 'serialize';

        /**
         * Target options.
         * @var array 
         */
        private $_options;

        /**
         * Constructor.
         * @param array $options The target options.
         * @param ModelInterface $model The target model.
         */
        public function __construct($options, $model)
        {
                if (!isset($options['format'])) {
                        $options['format'] = self::FORMAT_SERIALIZE;
                }
                if (!isset($options['name'])) {
                        $options['name'] = $this->getTargetFile($model);
                }

                $this->_options = $options;
        }

        /**
         * Write changes to file.
         * 
         * @param array $changes The model changes.
         * @return int 
         */
        public function write($changes)
        {
                switch ($this->_options['format']) {
                        case self::FORMAT_EXPORT:
                                return file_put_contents($this->_options['name'], var_export($changes, true) . ",\n", FILE_APPEND);
                        case self::FORMAT_JSON:
                                return file_put_contents($this->_options['name'], json_encode($changes) . "\n", FILE_APPEND);
                        case self::FORMAT_SERIALIZE:
                                return file_put_contents($this->_options['name'], serialize($changes) . "\n", FILE_APPEND);
                }
        }

        /**
         * Get target file path.
         * @param ModelInterface $model The target model.
         * @return boolean
         */
        private function getTargetFile($model)
        {
                $config = $this->getDI()->get('config');

                if (!file_exists($config->application->auditDir)) {
                        if (mkdir($config->application->auditDir)) {
                                return sprintf("%s/%s.dat", $config->application->auditDir, $model->getResourceName());
                        }
                }

                return false;
        }

}
