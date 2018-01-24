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
// File:    CacheTask.php
// Created: 2016-01-25 20:13:51
// 
// Author:  Anders Lövgren (QNET/BMC CompDept)
// 

namespace OpenExam\Console\Tasks;

use OpenExam\Models\Exam;

/**
 * Cache maintenance task.
 * 
 * Notice that APC cache for web server is not available for CLI tasks. Other
 * cache backends should work though.
 * 
 * @author Anders Lövgren (QNET/BMC CompDept)
 * @see https://forum.phalconphp.com/discussion/4529/access-to-cache-in-cli-task-not-working-with-apc
 */
class CacheTask extends MainTask implements TaskInterface
{

        /**
         * Runtime options
         * @var array 
         */
        private $_options;

        public function helpAction()
        {
                parent::showUsage(self::getUsage());
        }

        public static function getUsage()
        {
                return array(
                        'header'   => 'Cache maintenance tool.',
                        'action'   => '--cache',
                        'usage'    => array(
                                '--clean [--key=name]',
                                '--query [--key=name]',
                                '--fill [--days=num]',
                                '--info'
                        ),
                        'options'  => array(
                                '--clean'    => 'Cleanup cache.',
                                '--query'    => 'Query cache entries.',
                                '--info'     => 'Show cache information.',
                                '--fill'     => 'Fill cache with exam data',
                                '--days=num' => 'Fill cache for num days',
                                '--key=name' => 'Match cache key name (e.g. acl, ldap or roles).',
                                '--count'    => 'Count matching keys',
                                '--verbose'  => 'Be more verbose.'
                        ),
                        'examples' => array(
                                array(
                                        'descr'   => 'Delete all cache entries',
                                        'command' => '--clean'
                                ),
                                array(
                                        'descr'   => 'Cleanup all cached LDAP results',
                                        'command' => '--clean --key=ldap'
                                ),
                                array(
                                        'descr'   => 'Query cache roles',
                                        'command' => '--query --key=roles'
                                ),
                                array(
                                        'descr'   => 'Fill cache with 5 days of exam data',
                                        'command' => '--fill --days=5'
                                )
                        )
                );
        }

        /**
         * Cleanup cache.
         * @param array $params Task action parameters.
         */
        public function cleanAction($params = array())
        {
                $this->setOptions($params, 'query');

                foreach ($this->cache->getBackends() as $backend) {
                        if ($this->_options['key'] == false) {
                                $backend->flush();
                                continue;
                        }

                        if ($this->_options['prefix']) {
                                $prefix = $this->_options['prefix'];
                        } else {
                                $prefix = $backend->getOptions()['prefix'];
                        }

                        $find = sprintf("%s%s", $prefix, $this->_options['key']);
                        $keys = $backend->queryKeys($find);

                        foreach ($keys as $key) {
                                $backend->delete(substr($key, strlen($prefix)));
                        }
                }
        }

        /**
         * Query cache entries.
         * @param array $params Task action parameters.
         */
        public function queryAction($params = array())
        {
                $this->setOptions($params, 'query');

                foreach ($this->cache->getBackends() as $backend) {
                        if ($this->_options['prefix']) {
                                $prefix = $this->_options['prefix'];
                        } else {
                                $prefix = $backend->getOptions()['prefix'];
                        }

                        if ($this->_options['key']) {
                                $find = sprintf("%s%s", $prefix, $this->_options['key']);
                                $name = get_class($backend);
                                $keys = $backend->queryKeys($find);
                        } else {
                                $name = get_class($backend);
                                $keys = $backend->queryKeys();
                        }

                        if ($this->_options['count']) {
                                $this->flash->success(sprintf("%s:\t%d", $name, count($keys)));
                        } else {
                                $this->flash->success(sprintf("%s:\t%s", $name, print_r($keys, true)));
                        }

                        if ($this->_options['verbose']) {
                                foreach ($keys as $key) {
                                        $data = $backend->get(substr($key, strlen($prefix)));
                                        $this->flash->success(sprintf("%s: %s\n", $key, print_r($data, true)));
                                }
                        }
                }
        }

        /**
         * Show cache information.
         * @param array $params Task action parameters.
         */
        public function infoAction($params = array())
        {
                $this->setOptions($params, 'query');

                foreach ($this->cache->getBackends() as $backend) {
                        $name = get_class($backend);
                        $data = array(
                                'started'  => $backend->isStarted(),
                                'fresh'    => $backend->isFresh(),
                                'last key' => $backend->getLastKey(),
                                'options'  => $backend->getOptions(),
                                'frontend' => array(
                                        'buffering' => $backend->getFrontend()->isBuffering(),
                                        'lifetime'  => $backend->getFrontend()->getLifetime()
                                )
                        );
                        $this->flash->success(sprintf("%s:\t%s\n", $name, print_r($data, true)));
                }
        }

        /**
         * Fill cache with exam data.
         * @param array $params Task action parameters.
         */
        public function fillAction($params = array())
        {
                $this->setOptions($params, 'fill');

                $this->_options['curr'] = strftime(
                    "%Y-%m-%d %H:%M:%S", time()
                );
                $this->_options['time'] = strftime(
                    "%Y-%m-%d %H:%M:%S", time() + 24 * 3600 * intval($this->_options['days'])
                );

                if (!($exams = Exam::find(array(
                            'conditions' => 'endtime BETWEEN :curr: AND :time:',
                            'bind'       => array(
                                    'curr' => $this->_options['curr'],
                                    'time' => $this->_options['time']
                            )
                    )))) {
                        throw new Exception("Failed fetch upcoming exams");
                }

                foreach ($exams as $exam) {
                        $this->fillCache($exam);
                }
        }

        /**
         * Fill cache data for exam.
         * @param Exam $exam The exam model.
         */
        private function fillCache($exam)
        {
                if ($this->_options['verbose']) {
                        $this->flash->success(sprintf("Filling application cache for exam %d", $exam->id));
                }
                if (!extension_loaded('curl')) {
                        throw new Exception("The curl extension is not loaded");
                }

                try {
                        // 
                        // Can't fill web cache from CLI:
                        // 
                        $host = "http://127.0.0.1";
                        $path = $this->url->get(sprintf("/utility/cache/fill/%d", $exam->id));

                        if (!($curl = curl_init(sprintf("%s%s", $host, $path)))) {
                                throw new Exception("Failed initialize cURL");
                        }

                        if (!(curl_exec($curl))) {
                                throw new Exception(curl_error($curl));
                        }
                } finally {
                        if (isset($curl)) {
                                curl_close($curl);
                        }
                }
        }

        /**
         * Set options from task action parameters.
         * @param array $params The task action parameters.
         * @param string $action The calling action.
         */
        private function setOptions($params, $action = null)
        {
                // 
                // Default options.
                // 
                $this->_options = array('verbose' => false, 'days' => 3);

                // 
                // Supported options.
                // 
                $options = array('verbose', 'clean', 'query', 'info', 'fill', 'days', 'prefix', 'key', 'count');
                $current = $action;

                // 
                // Set defaults.
                // 
                foreach ($options as $option) {
                        if (!isset($this->_options[$option])) {
                                $this->_options[$option] = false;
                        }
                }

                // 
                // Include action in options (for multitarget actions).
                // 
                if (isset($action)) {
                        $this->_options[$action] = true;
                }

                // 
                // Scan params for both --key and --key=val options.
                // 
                while (($option = array_shift($params))) {
                        if (in_array($option, $options)) {
                                $this->_options[$option] = true;
                                $current = $option;
                        } elseif (in_array($current, $options)) {
                                $this->_options[$current] = $option;
                        } else {
                                throw new Exception("Unknown task action/parameters '$option'");
                        }
                }
        }

}
