<?php

/*
 * Copyright (C) 2014-2018 The OpenExam Project
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
// File:    Filter.php
// Created: 2014-10-28 12:45:17
// 
// Author:  Anders Lövgren (Computing Department at BMC, Uppsala University)
// 

namespace OpenExam\Library\Model;

use Phalcon\Mvc\Model;

/**
 * Functional object for query result filter.
 * 
 * This filter can be attached on a result set to filter those object that
 * should be part of the result (using the filter method).
 * 
 * Example on filtering out upcoming examinations:
 * <code>
 * $result = Exam::find(...);
 * $result->filter(new Filter(array('state' => 64));                    // Same
 * $result->filter(new Filter(array('flags' => 'upcoming'));            // Same
 * $result->filter(new Filter(array('flags' => array('upcoming')));     // Same
 * </code>
 * 
 * Notice that the filtering is done on the result set after query the model.
 * 
 * @author Anders Lövgren (Computing Department at BMC, Uppsala University)
 */
class Filter
{

        /**
         * The filter parameters.
         * @var array 
         */
        private $_params;

        /**
         * Constructor.
         * @param array $params The filter parameters.
         */
        public function __construct($params)
        {
                $this->_params = $params;
        }

        /**
         * Set filter parameters.
         * @param array $params The filter parameters.
         */
        public function setFilter($params)
        {
                $this->_params = $params;
        }

        /**
         * Check if model should be included in the result set.
         * @param Model $model The model object.
         * @return bool 
         */
        public function accept($model)
        {
                foreach ($this->_params as $key => $val) {
                        if (isset($model->$key)) {
                                $k = $model->$key;
                                $v = $val;
                                if (is_array($v)) {
                                        foreach ($v as $a) {
                                                if (!in_array($a, $k)) {
                                                        return false;
                                                }
                                        }
                                } elseif (is_array($k)) {
                                        if (!in_array($v, $k)) {
                                                return false;
                                        }
                                } elseif ($k != $v) {
                                        return false;
                                }
                        }
                }
                return true;
        }

        /**
         * Allow invocation as functional object.
         * @return bool|Model
         */
        public function __invoke()
        {
                $model = func_get_arg(0);
                return $this->accept($model) ? $model : false;
        }

}
