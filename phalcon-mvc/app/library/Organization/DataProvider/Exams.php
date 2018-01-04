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
// File:    Exams.php
// Created: 2016-05-13 03:19:11
// 
// Author:  Anders Lövgren (QNET/BMC CompDept)
// 

namespace OpenExam\Library\Organization\DataProvider;

use OpenExam\Models\Exam;
use Phalcon\Mvc\User\Component;

/**
 * The exams data provider.
 * 
 * By default, calling getData() will return entries in this format:
 * <code>
 * array(
 *      'id'         => int,
 *      'name'       => string
 *      'starttime'  => timestamp,
 *      'endtime'    => timestamp,
 *      'division'   => string,
 *      'department' => string
 * )
 * </code>
 *
 * @author Anders Lövgren (QNET/BMC CompDept)
 */
class Exams extends Component
{

        /**
         * The exams filter.
         * @var array
         */
        private $_filter;
        /**
         * The columns to query.
         * @var string 
         */
        private $_columns;
        /**
         * The query conditions.
         * @var string 
         */
        private $_conditions;

        /**
         * Constructor.
         * @param array $filter The exams filter.
         * @param string $columns The columns to query.
         */
        public function __construct($filter, $columns = "id,name,starttime,endtime,division,department")
        {
                $this->_filter = $filter;
                $this->_columns = $columns;
                $this->_conditions = $this->getConditions();
        }

        /**
         * Destructor.
         */
        public function __destruct()
        {
                unset($this->_columns);
                unset($this->_conditions);
                unset($this->_filter);
        }

        /**
         * Set exams filter.
         * @param array $filter The exams filter.
         */
        public function setFilter($filter)
        {
                $this->_filter = $filter;
        }

        /**
         * Get exams filter.
         * @return array
         */
        public function getFilter()
        {
                return $this->_filter;
        }

        /**
         * Set columns returned by getData().
         * @param array $columns The columns to return.
         */
        public function setColumns($columns)
        {
                $this->_columns = implode(",", $columns);
        }

        /**
         * Get exams data using current filter.
         * 
         * The data returned will have this format unless a custom columns
         * condition has been set:
         * <code>
         * array(
         *      'id'        => int,             // The exam ID
         *      'name'      => string,          // The exam name
         *      'starttime' => datetime,        // Start time
         *      'endtime'   => datetime         // End time
         * )
         * </code>
         * 
         * @return array
         */
        public function getData()
        {
                if (($find = Exam::find(array(
                            'columns'    => $this->_columns,
                            'conditions' => $this->_conditions,
                            'bind'       => $this->_filter,
                            'order'      => 'starttime DESC'
                    )))) {
                        return $find->toArray();
                }
        }

        /**
         * Get number of exams matching current filter.
         * @return int
         */
        public function getSize()
        {
                return Exam::count(array(
                            'conditions' => $this->_conditions,
                            'bind'       => $this->_filter
                ));
        }

        /**
         * Get query conditions.
         * @return string
         */
        private function getConditions()
        {
                switch (count($this->_filter)) {
                        case 0:
                                return "published = 'Y'";
                        case 1:
                                return "division = :division: AND published = 'Y'";
                        case 2:
                                return "division = :division: AND department = :department: AND published = 'Y'";
                        case 3:
                                return "division = :division: AND department = :department: AND workgroup = :workgroup: AND published = 'Y'";
                }
        }

}
