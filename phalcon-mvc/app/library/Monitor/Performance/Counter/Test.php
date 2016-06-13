<?php

// 
// The source code is copyrighted, with equal shared rights, between the
// authors (see the file AUTHORS) and the OpenExam project, Uppsala University 
// unless otherwise explicit stated elsewhere.
// 
// File:    Test.php
// Created: 2016-06-04 16:43:56
// 
// Author:  Anders Lövgren (QNET/BMC CompDept)
// 

namespace OpenExam\Library\Monitor\Performance\Counter;

use OpenExam\Library\Monitor\Performance\Counter;

/**
 * Test performance counter.
 *
 * @see \OpenExam\Library\Monitor\Performance\Collector\Test
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
