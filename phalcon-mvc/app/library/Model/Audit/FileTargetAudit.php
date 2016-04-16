<?php

// 
// The source code is copyrighted, with equal shared rights, between the
// authors (see the file AUTHORS) and the OpenExam project, Uppsala University 
// unless otherwise explicit stated elsewhere.
// 
// File:    FileTargetAudit.php
// Created: 2016-04-15 15:49:54
// 
// Author:  Anders Lövgren (Computing Department at BMC, Uppsala University)
// 

namespace OpenExam\Library\Model\Audit;

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
class FileTargetAudit extends Audit
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
         * Receives notifications from the Models Manager
         *
         * @param string $type The event type.
         * @param ModelInterface $model The target model.
         */
        public function notify($type, $model)
        {
                $options = $this->getOptions();

                if (!isset($options)) {
                        $options = array();
                }
                if (!isset($options['actions'])) {
                        $options['actions'] = array('create', 'update', 'delete');
                }
                if (!isset($options['format'])) {
                        $options['format'] = self::FORMAT_SERIALIZE;
                }
                if (!isset($options['file'])) {
                        $options['file'] = $this->getTargetFile($model);
                }

                $actions = $options['actions'];

                if (!parent::hasChanges($type, $actions)) {
                        return false;
                }
                if (($changes = parent::getChanges($type, $model))) {
                        return $this->write($changes, $options);
                }
        }

        /**
         * Write changes to file.
         * 
         * @param array $changes The model changes.
         * @param array $options The behavior options.
         * @return int 
         */
        private function write($changes, $options, $di = null)
        {
                switch ($options['format']) {
                        case self::FORMAT_EXPORT:
                                return file_put_contents($options['file'], var_export($changes, true) . ",\n", FILE_APPEND);
                        case self::FORMAT_JSON:
                                return file_put_contents($options['file'], json_encode($changes) . "\n", FILE_APPEND);
                        case self::FORMAT_SERIALIZE:
                                return file_put_contents($options['file'], serialize($changes) . "\n", FILE_APPEND);
                }
        }

        /**
         * Get target file path.
         * @param ModelInterface $model The target model.
         * @return boolean
         */
        private function getTargetFile($model)
        {
                $config = $model->getDI()->get('config');

                if (!file_exists($config->application->auditDir)) {
                        if (mkdir($config->application->auditDir)) {
                                return sprintf("%s/%s.dat", $config->application->auditDir, $model->getResourceName());
                        }
                }

                return false;
        }

}
