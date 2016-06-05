<?php

// 
// The source code is copyrighted, with equal shared rights, between the
// authors (see the file AUTHORS) and the OpenExam project, Uppsala University 
// unless otherwise explicit stated elsewhere.
// 
// File:    CounterQuery.php
// Created: 2016-05-30 04:38:14
// 
// Author:  Anders Lövgren (QNET/BMC CompDept)
// 

namespace OpenExam\Library\Monitor\Performance\Counter;

use OpenExam\Library\Monitor\Exception;
use OpenExam\Models\Performance;

/**
 * Performance counter query.
 *
 * @author Anders Lövgren (QNET/BMC CompDept)
 */
class CounterQuery
{

        /**
         * Allowed filter keys.
         * @var array 
         */
        private static $permitted = array(
                'mode', 'source', 'time', 'host', 'addr', 'milestone'
        );

        /**
         * Get performance counter data.
         * 
         * @param string $mode The counter name.
         * @param array $filter The query filter.
         * @param int $limits Limit on returned records.
         * @return array
         * 
         * @throws Exception
         */
        public static function getData($mode, $filter, $limits)
        {
                // 
                // Strip invalid search keys:
                // 
                foreach (array_keys($filter) as $key) {
                        if (!in_array($key, self::$permitted)) {
                                unset($filter[$key]);
                        } elseif (!$filter[$key]) {
                                unset($filter[$key]);
                        }
                }

                if (!isset($filter['addr'])) {
                        $filter['addr'] = gethostbyname(gethostname());
                } elseif ($filter['addr'] == '*') {
                        unset($filter['addr']);
                }

                if (isset($mode)) {
                        $filter['mode'] = $mode;
                }

                $conditions = array();

                foreach ($filter as $key => $val) {
                        if ($key == 'time') {
                                $conditions[] = "$key LIKE \"$val%\"";
                                unset($filter['time']);
                        } else {
                                $conditions[] = "$key = :$key:";
                        }
                }

                if (($result = Performance::find(array(
                            'conditions' => implode(" AND ", $conditions),
                            'bind'       => $filter,
                            'order'      => 'time DESC',
                            'limit'      => $limits
                    )))) {
                        $data = array();
                        foreach ($result as $model) {
                                $data[] = $model->toArray();
                        }
                        return array_reverse($data);
                } else {
                        throw new Exception("Failed query performance model");
                }
        }

        /**
         * Return an array of distinct source names.
         * 
         * @param string $mode The counter name.
         * @return array
         * 
         * @throws Exception
         */
        public static function getSources($mode)
        {
                if (($result = Performance::find(array(
                            'columns'    => "source",
                            'conditions' => "mode = :mode:",
                            'group'      => "source",
                            'order'      => "source",
                            'bind'       => array(
                                    'mode' => $mode
                            )
                    )))) {
                        $sources = array();
                        foreach ($result as $row) {
                                if ($row['source'] != 'lo' && strlen($row['source']) != 0) {
                                        $sources[] = $row['source'];
                                }
                        }
                        return $sources;
                } else {
                        throw new Exception("Failed query performance model");
                }
        }

        /**
         * Return an array of address and hostnames.
         * 
         * @param string $mode The counter name.
         * @return array
         * 
         * @throws Exception
         */
        public static function getAddresses($mode)
        {
                if (($result = Performance::find(array(
                            'columns'    => "host,addr",
                            'conditions' => "mode = :mode:",
                            'order'      => "addr",
                            'group'      => array("addr", "host"),
                            'bind'       => array(
                                    'mode' => $mode
                            )
                    )))) {
                        return $result->toArray();
                } else {
                        throw new Exception("Failed query performance model");
                }
        }

}
