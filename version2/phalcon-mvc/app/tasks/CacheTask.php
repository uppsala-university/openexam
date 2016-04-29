<?php

// 
// The source code is copyrighted, with equal shared rights, between the
// authors (see the file AUTHORS) and the OpenExam project, Uppsala University 
// unless otherwise explicit stated elsewhere.
// 
// File:    CacheTask.php
// Created: 2016-01-25 20:13:51
// 
// Author:  Anders Lövgren (QNET/BMC CompDept)
// 

namespace OpenExam\Console\Tasks;

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
                                '--info'
                        ),
                        'options'  => array(
                                '--clean'    => 'Cleanup cache.',
                                '--query'    => 'Query cache entries.',
                                '--info'     => 'Show cache information.',
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
         * Set options from task action parameters.
         * @param array $params The task action parameters.
         * @param string $action The calling action.
         */
        private function setOptions($params, $action = null)
        {
                // 
                // Default options.
                // 
                $this->_options = array('verbose' => false);

                // 
                // Supported options.
                // 
                $options = array('verbose', 'clean', 'query', 'info', 'prefix', 'key', 'count');
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
