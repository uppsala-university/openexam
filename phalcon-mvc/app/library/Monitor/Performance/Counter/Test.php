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
// File:    Test.php
// Created: 2016-06-04 16:43:56
// 
// Author:  Anders Lövgren (QNET/BMC CompDept)
// 

namespace OpenExam\Library\Monitor\Performance\Counter;

use OpenExam\Library\Monitor\Performance\Collector\Test;
use OpenExam\Library\Monitor\Performance\Counter;

/**
 * Test performance counter.
 *
 * @see Test
 * @author Anders Lövgren (QNET/BMC CompDept)
 */
class Test extends CounterBase implements Counter
{

        const TYPE = 'test';

        public function __construct($performance)
        {
                parent::__construct(self::TYPE, $performance);
        }

        public function getDescription()
        {
                return "Test performance counter";
        }

        public function getKeys()
        {
                return array(
                        'label'  => $this->getName(),
                        'descr'  => $this->getDescription(),
                        'const'  => array(
                                'label' => 'Const',
                                'descr' => 'The contant values sequence',
                                'value' => array(
                                        'label' => 'Value',
                                        'descr' => 'The sequence value'
                                )
                        ),
                        'linear' => array(
                                'label' => 'Linear',
                                'descr' => 'The linear values sequence',
                                'value' => array(
                                        'label' => 'Value',
                                        'descr' => 'The sequence value'
                                )
                        ),
                        'exp'    => array(
                                'label' => 'Exponential',
                                'descr' => 'The exponential values sequence',
                                'value' => array(
                                        'label' => 'Value',
                                        'descr' => 'The sequence value'
                                )
                        )
                );
        }

        public function getName()
        {
                return "Test";
        }

        public function getTitle()
        {
                return "Test";
        }

        public function hasCounter($type)
        {
                return $type == 'const' || $type == 'linean' || $type == 'exp';
        }

}
