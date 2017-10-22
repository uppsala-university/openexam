<?php

// 
// The source code is copyrighted, with equal shared rights, between the
// authors (see the file AUTHORS) and the OpenExam project, Uppsala University 
// unless otherwise explicit stated elsewhere.
// 
// File:    Replace.php
// Created: 2017-09-20 13:25:42
// 
// Author:  Anders Lövgren (Computing Department at BMC, Uppsala University)
// 

namespace OpenExam\Library\Model\Behavior\Transform;

use OpenExam\Library\Model\Behavior\ModelBehavior;
use Phalcon\Mvc\ModelInterface;

/**
 * String replace on field.
 * 
 * This behavior performs a search and replace of substring on requested 
 * model property.
 *
 * @author Anders Lövgren (Computing Department at BMC, Uppsala University)
 */
class Replace extends ModelBehavior
{

        public function notify($type, ModelInterface $model)
        {
                if (($options = $this->getOptions($type))) {

                        $field = $options['field'];
                        $input = $model->$field;

                        if (isset($options['search']) && isset($options['replace'])) {
                                $model->$field = str_replace($options['search'], $options['replace'], $input);
                        }

                        return true;
                }
        }

}
