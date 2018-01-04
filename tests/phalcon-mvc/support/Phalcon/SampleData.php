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
// File:    SampleData.php
// Created: 2014-11-25 11:07:51
// 
// Author:  Anders Lövgren (Computing Department at BMC, Uppsala University)
// 

namespace OpenExam\Tests\Phalcon;

use Phalcon\DI as PhalconDI;

/**
 * Handle model sample data.
 * @author Anders Lövgren (Computing Department at BMC, Uppsala University)
 */
class SampleData
{

        /**
         * @var array 
         */
        private $_sample;

        /**
         * @param string|array $sample The sample data file or array.
         */
        public function __construct($sample = null)
        {
                $config = PhalconDI::getDefault()->get('config');

                if (!isset($sample)) {
                        $sample = sprintf("%s/unittest/sample.dat", $config->application->cacheDir);
                }

                if (is_array($sample)) {
                        $this->_sample = $sample;
                } elseif (file_exists($sample)) {
                        $this->_sample = unserialize(file_get_contents($sample));
                }

                if (!isset($this->_sample)) {
                        throw new \Exception("The sample data is missing. Please run 'php phalcon-mvc/script/unittest.php --setup'");
                }
        }

        /**
         * Get sample data for resource.
         * @param string $resource The resource name (e.g. question).
         * @param bool $object Return ID also if true.
         * @return array
         */
        public function getSample($resource, $object = true)
        {
                $data = (array) $this->_sample[$resource];

                foreach ($data as $key => $val) {
                        if (is_object($val)) {
                                unset($data[$key]);
                        }
                }

                if ($object) {
                        return $data;
                } else {
                        unset($data['id']);
                        return $data;
                }
        }

}
