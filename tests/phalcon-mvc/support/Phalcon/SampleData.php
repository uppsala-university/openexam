<?php

// 
// The source code is copyrighted, with equal shared rights, between the
// authors (see the file AUTHORS) and the OpenExam project, Uppsala University 
// unless otherwise explicit stated elsewhere.
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
        private $sample;

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
                        $this->sample = $sample;
                } elseif (file_exists($sample)) {
                        $this->sample = unserialize(file_get_contents($sample));
                }

                if (!isset($this->sample)) {
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
                $data = (array) $this->sample[$resource];

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
