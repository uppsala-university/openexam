<?php

// 
// The source code is copyrighted, with equal shared rights, between the
// authors (see the file AUTHORS) and the OpenExam project, Uppsala University 
// unless otherwise explicit stated elsewhere.
// 
// File:    Maximum.php
// Created: 2017-04-21 00:44:45
// 
// Author:  Anders Lövgren (QNET/BMC CompDept)
// 

namespace OpenExam\Library\Model\Behavior\Generate;

use OpenExam\Library\Model\Behavior\ModelBehavior;
use Phalcon\Mvc\ModelInterface;

/**
 * Maximum unique value generator.
 *
 * @author Anders Lövgren (QNET/BMC CompDept)
 */
class Maximum extends ModelBehavior
{

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

                        $params = array(
                                sprintf("%s = %d", $limit, $model->$limit),
                                'column' => "$field"
                        );
                        $count = sprintf("%s = %d AND %s = %d", $limit, $model->$limit, $field, $model->$field);

                        if (!isset($model->$field)) {
                                $model->$field = $model->maximum($params) + 1;
                        } elseif ($model->count($count) != 0) {
                                $model->$field = $model->maximum($params) + 1;
                        }
                }
        }

}
