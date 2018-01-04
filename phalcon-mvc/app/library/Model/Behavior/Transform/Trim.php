<?php

/*
 * Copyright (C) 2017-2018 The OpenExam Project
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
 * to replace with is optional and defaults to null.
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
                                if (strstr($model->$f, "&nbsp;")) {
                                        $model->$f = str_replace("&nbsp;", " ", $model->$f);
                                }
                                if (strlen(trim($model->$f)) == 0) {
                                        $model->$f = $value;
                                }
                        }

                        return true;
                }
        }

}
