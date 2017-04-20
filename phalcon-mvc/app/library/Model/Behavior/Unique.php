<?php

// 
// The source code is copyrighted, with equal shared rights, between the
// authors (see the file AUTHORS) and the OpenExam project, Uppsala University 
// unless otherwise explicit stated elsewhere.
// 
// File:    Unique.php
// Created: 2017-04-21 01:46:33
// 
// Author:  Anders Lövgren (QNET/BMC CompDept)
// 

namespace OpenExam\Library\Model\Behavior;

use Phalcon\Mvc\ModelInterface;

/**
 * Unique field value generator.
 *
 * @author Anders Lövgren (QNET/BMC CompDept)
 */
class Unique extends ModelBehavior
{

        /**
         * Default format string.
         */
        const FORMAT = 'UUID%d';

        /**
         * Receives notifications from the Models Manager
         *
         * @param string $type The event type.
         * @param ModelInterface $model The target model.
         */
        public function notify($type, ModelInterface $model)
        {
                if (($options = $this->getOptions($type))) {
                        $field = $options['field'];
                        $limit = $options['limit'];

                        if (!isset($options['format'])) {
                                $format = self::FORMAT;
                        } else {
                                $format = $options['format'];
                        }

                        $count = sprintf("%s = %d AND %s = '%s'", $limit, $model->$limit, $field, $model->$field);

                        if (!isset($model->$field)) {
                                $model->$field = sprintf($format, time());
                        } elseif ($model->count($count) != 0) {
                                $model->$field = sprintf($format, time());
                        }
                }
        }

}
