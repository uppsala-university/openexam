<?php

// 
// The source code is copyrighted, with equal shared rights, between the
// authors (see the file AUTHORS) and the OpenExam project, Uppsala University 
// unless otherwise explicit stated elsewhere.
// 
// File:    Groups.php
// Created: 2017-04-11 22:26:45
// 
// Author:  Anders Lövgren (QNET/BMC CompDept)
// 

namespace OpenExam\Library\Catalog\Manager\Search;

use OpenExam\Library\Catalog\DirectoryManager;
use OpenExam\Library\Catalog\DirectoryService;
use OpenExam\Library\Catalog\Exception;
use OpenExam\Library\Catalog\Group;
use OpenExam\Library\Catalog\Manager\Search;

/**
 * Directory groups search.
 *
 * @author Anders Lövgren (QNET/BMC CompDept)
 */
class Groups implements Search
{

        /**
         * The user principal name.
         * @var string 
         */
        private $_principal;
        /**
         * The attributes filter.
         * @var array 
         */
        private $_filter;

        /**
         * Constructor.
         * @param string $principal The user principal name.
         * @param array $attributes The attribute filter.
         */
        public function __construct($principal, $attributes = null)
        {
                if (empty($attributes)) {
                        $attributes = array(Group::ATTR_NAME);
                }

                $this->_principal = $principal;
                $this->_filter = $attributes;
        }

        /**
         * Set attributes filter.
         * @param array $attributes The returned attributes.
         */
        public function setFilter($attributes)
        {
                $this->_filter = $attributes;
        }

        /**
         * Set user principal.
         * @param string $principal The user principal name.
         */
        public function setPrincipal($principal)
        {
                $this->_principal = $principal;
        }

        /**
         * Get directory manager search result.
         * @param DirectoryManager $manager The directory manager.
         * @return array
         */
        public function getResult($manager)
        {
                $domain = $manager->getRealm($this->_principal);
                $result = array();

                foreach ($manager->getServices($domain) as $name => $service) {
                        if (($groups = $this->getGroups($manager, $service, $name)) != null) {
                                $result = array_merge($result, $groups);
                        }
                }

                return $result;
        }

        /**
         * Get directory groups.
         * 
         * @param DirectoryManager $manager The directory manager.
         * @param DirectoryService $service The directory service.
         * @param string $name The service name.
         * @return array
         */
        private function getGroups($manager, $service, $name)
        {
                try {
                        return $service->getGroups($this->_principal, $this->_filter);
                } catch (Exception $exception) {
                        $manager->report($exception, $service, $name);
                }
        }

}
