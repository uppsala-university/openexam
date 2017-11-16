<?php

// 
// The source code is copyrighted, with equal shared rights, between the
// authors (see the file AUTHORS) and the OpenExam project, Uppsala University 
// unless otherwise explicit stated elsewhere.
// 
// File:    Trim.php
// Created: 2017-11-16 04:05:33
// 
// Author:  Anders Lövgren (QNET)
// 

namespace OpenExam\Library\Model\Behavior\Transform;

use OpenExam\Library\Model\Behavior\ModelBehavior;
use Phalcon\Mvc\ModelInterface;

/**
 * Trim value on fields.
 * 
 * The field option is either a string or an array of fields to trim. If trimming
 * the field result in an empty string, then replace the field value. The value
 * to replace with is optional an defaults to null.
 *
 * @author Anders Lövgren (QNET)
 */
class Trim extends ModelBehavior
{

        public function notify($type, ModelInterface $model)
        {
                if (($options = $this->getOptions($type))) {

                        if (!isset($options['value'])) {
                                $options['value'] = null;
                        }

                        $field = $options['field'];
                        $value = $options['value'];

                        if (!is_array($field)) {
                                $field = array($field);
                        }

                        foreach ($field as $f) {
                                if (!isset($model->$f)) {
                                        continue;
                                }
                                if (strlen(trim($model->$f)) == 0) {
                                        $model->$f = $value;
                                }
                        }
                        
                        return true;
                }
        }

}
