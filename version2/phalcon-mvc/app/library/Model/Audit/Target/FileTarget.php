<?php

// 
// The source code is copyrighted, with equal shared rights, between the
// authors (see the file AUTHORS) and the OpenExam project, Uppsala University 
// unless otherwise explicit stated elsewhere.
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
