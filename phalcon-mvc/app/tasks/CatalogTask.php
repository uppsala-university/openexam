<?php

// 
// The source code is copyrighted, with equal shared rights, between the
// authors (see the file AUTHORS) and the OpenExam project, Uppsala University 
// unless otherwise explicit stated elsewhere.
// 
// File:    CatalogTask.php
// Created: 2016-11-14 07:22:00
// 
// Author:  Anders Lövgren (QNET/BMC CompDept)
// 

namespace OpenExam\Console\Tasks;

/**
 * Catalog service task.
 *
 * @author Anders Lövgren (QNET/BMC CompDept)
 */
class CatalogTask extends MainTask implements TaskInterface
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
                        'header'   => 'Catalog service query tool',
                        'action'   => '--catalog',
                        'usage'    => array(
                                '--principal --needle=val --search=attr',
                                '--attribute --principal=user',
                                '--groups --principal=user',
                                '--members --group=name'
                        ),
                        'options'  => array(
                                '--principal'      => 'Search for user principal.',
                                '--principal=user' => 'User principal as search parameter.',
                                '--search=attr'    => 'Search for attribute (user principal search).',
                                '--needle=val'     => 'The attribute value (user principal search).',
                                '--attribute'      => 'Search for user attribute(s).',
                                '--groups'         => 'Search for groups where user principal is member.',
                                '--group=name'     => 'Use group name as search parameter for members listing.',
                                '--members'        => 'Search for group members.',
                                '--verbose'        => 'Be more verbose.'
                        ),
                        'examples' => array(
                                array(
                                        'descr'   => 'Get user principals with given name in domain user.uu.se',
                                        'command' => '--catalog --principal --search=gn --needle=Anders --domain=user.uu.se'
                                )
                        )
                );
        }

        /**
         * Group search action.
         * @param array $params
         */
        public function groupsAction($params = array())
        {
                $this->setOptions($params, 'group');
                $result = $this->catalog->getGroups($this->_options['principal']);
                print_r($result);
        }

        /**
         * Members search action.
         * @param array $params
         */
        public function membersAction($params = array())
        {
                $this->setOptions($params, 'members');
                $result = $this->catalog->getMembers($this->_options['group']);
                print_r($result);
        }

        /**
         * Attribute search action.
         * @param array $params
         */
        public function attributeAction($params = array())
        {
                $this->setOptions($params, 'attribute');
                $result = $this->catalog->getAttribute($this->_options['principal']);
                print_r($result);
        }

        /**
         * Principal search action.
         * @param array $params
         */
        public function principalAction($params = array())
        {
                $this->setOptions($params, 'principal');
                $result = $this->catalog->getPrincipal($this->_options['needle'], $this->_options['search'], array('domain' => $this->_options['domain']));
                print_r($result);
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
                $options = array('verbose', 'groups', 'group', 'members', 'principal', 'attribute', 'domain', 'needle', 'search');
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

                print_r($this->_options);
        }

}
