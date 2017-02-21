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

use OpenExam\Library\Catalog\DirectoryService;

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
        /**
         * The directory service.
         * @var DirectoryService
         */
        private $_service;

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
                                '--principal --needle=val --search=attr [--domain=name] [--service=name] [--limit=num]',
                                '--attribute=name --principal=user',
                                '--attributes=name --principal=user',
                                '--groups --principal=user',
                                '--members --group=name',
                                '--domains|--services'
                        ),
                        'options'  => array(
                                '--principal'       => 'Search for user principal.',
                                '--principal=user'  => 'User principal as search parameter.',
                                '--search=attr'     => 'Search for attribute (user principal search).',
                                '--needle=val'      => 'The attribute value (user principal search).',
                                '--attribute=name'  => 'Search for user attributes.',
                                '--attributes=name' => 'Search for user attributes.',
                                '--groups'          => 'Search for groups where user principal is member.',
                                '--group=name'      => 'Use group name as search parameter for members listing.',
                                '--members'         => 'Search for group members.',
                                '--domain=name'     => 'Restrict search to given domain.',
                                '--service=name'    => 'Restrict search to given service.',
                                '--limit=num'       => 'Limit number of records returned from user principal search.',
                                '--domains'         => 'List all directory domains in catalog manager.',
                                '--services'        => 'List all services in catalog manager.',
                                '--verbose'         => 'Be more verbose.'
                        ),
                        'examples' => array(
                                array(
                                        'descr'   => 'List services in all domains',
                                        'command' => '--catalog --services'
                                ),
                                array(
                                        'descr'   => 'Get user principals with given name in domain user.uu.se',
                                        'command' => '--catalog --principal --search=gn --needle=Anders --domain=user.uu.se'
                                ),
                                array(
                                        'descr'   => 'Get user principals with given name in service akka',
                                        'command' => '--catalog --principal --search=gn --needle=Anders --service=akka --limit=15'
                                ),
                                array(
                                        'descr'   => 'Same, but restrict returned attributes',
                                        'command' => '--catalog --principal --search=gn --needle=Anders --service=akka --limit=15 --attributes=name,mail,uid,department'
                                ),
                                array(
                                        'descr'   => 'Get members of given group',
                                        'command' => '--catalog --members --group="BMC Mediateket CBE Manager"'
                                ),
                                array(
                                        'descr'   => 'Get user groups',
                                        'command' => '--catalog --groups --principal=user@example.com'
                                ),
                                array(
                                        'descr'   => 'Get all mail attribute for user',
                                        'command' => '--catalog --attributes=mail --principal=user@example.com'
                                ),
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
                $result = $this->_service->getGroups($this->_options['principal']);
                print_r($result);
        }

        /**
         * Members search action.
         * @param array $params
         */
        public function membersAction($params = array())
        {
                $this->setOptions($params, 'members');
                $result = $this->_service->getMembers($this->_options['group']);
                print_r($result);
        }

        /**
         * Attribute search action.
         * @param array $params
         */
        public function attributeAction($params = array())
        {
                $this->setOptions($params, 'attribute');
                $result = $this->_service->getAttribute($this->_options['attribute'], $this->_options['principal']);
                print_r($result);
        }

        /**
         * Attributes search action.
         * @param array $params
         */
        public function attributesAction($params = array())
        {
                $this->setOptions($params, 'attributes');
                $result = $this->_service->getAttributes($this->_options['attribute'], $this->_options['principal']);
                print_r($result);
        }

        /**
         * Principals search action.
         * @param array $params
         */
        public function principalAction($params = array())
        {
                $this->setOptions($params, 'principal');

                if ($this->_options['domain']) {
                        $this->_service->setDefaultDomain($this->_options['domain']);
                }
                if ($this->_options['attributes'] && strstr($this->_options['attributes'], ',')) {
                        $this->_options['attribute'] = explode(',', $this->_options['attributes']);
                }

                if ($this->_options['limit'] == 1) {
                        $result = $this->_service->getPrincipal(
                            $this->_options['needle'], $this->_options['search'], $this->_options['domain'], $this->_options['attribute']
                        );
                } else {
                        $result = $this->_service->getPrincipals(
                            $this->_options['needle'], $this->_options['search'], array(
                                'domain' => $this->_options['domain'],
                                'limit'  => $this->_options['limit'],
                                'attr'   => $this->_options['attribute']
                        ));
                }
                print_r($result);
        }

        /**
         * Doamin listing action.
         * @param array $params
         */
        public function domainsAction($params = array())
        {
                $this->setOptions($params, 'domains');
                $result = $this->_service->getDomains();
                print_r($result);
        }

        /**
         * Services listing action.
         * @param array $params
         */
        public function servicesAction($params = array())
        {
                $this->setOptions($params, 'services');
                $result = $this->_service->getDomains();
                foreach ($result as $domain) {
                        $this->flash->notice(sprintf("%s ->", $domain));
                        foreach ($this->_service->getServices($domain) as $service) {
                                $this->flash->notice(sprintf("\t%s", $service->getServiceName()));
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
                $this->_options = array('verbose' => false);

                // 
                // Supported options.
                // 
                $options = array('verbose', 'groups', 'group', 'members', 'principal', 'attribute', 'attributes', 'domain', 'service', 'limit', 'needle', 'search', 'domains', 'services');
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

                if ($action == 'attributes') {
                        $this->_options['attribute'] = $this->_options['attributes'];
                        unset($this->_options['attributes']);
                }
                if ($this->_options['service']) {
                        $this->_service = $this->catalog->getService($this->_options['service']);
                } else {
                        $this->_service = $this->catalog;
                }
        }

}
