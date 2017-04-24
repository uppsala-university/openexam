<?php

// 
// The source code is copyrighted, with equal shared rights, between the
// authors (see the file AUTHORS) and the OpenExam project, Uppsala University 
// unless otherwise explicit stated elsewhere.
// 
// File:    Principal.php
// Created: 2017-04-12 01:37:22
// 
// Author:  Anders Lövgren (QNET/BMC CompDept)
// 

namespace OpenExam\Library\Catalog\Manager\Search;

use OpenExam\Library\Catalog\DirectoryManager;
use OpenExam\Library\Catalog\DirectoryService;
use OpenExam\Library\Catalog\Exception;
use OpenExam\Library\Catalog\Manager\Search;
use OpenExam\Library\Catalog\Principal as UserPrincipal;

/**
 * Directory principal search.
 *
 * @author Anders Lövgren (QNET/BMC CompDept)
 */
class Principal implements Search
{

        /**
         * The attribute search string.
         * @var string 
         */
        private $_needle;
        /**
         * The attribute to query.
         * @var string 
         */
        private $_attrib;
        /**
         * The search domain.
         * @var string 
         */
        private $_domain;
        /**
         * The attributes to return.
         * @var array 
         */
        private $_inject;

        /**
         * Constructor.
         * 
         * @param DirectoryManager $manager The directory manager.
         * @param string $needle The attribute search string.
         * @param string $attrib The attribute to query (optional).
         * @param string $domain The search domain (optional).
         * @param array|string $inject The attributes to return (optional).
         */
        public function __construct($manager, $needle, $attrib = null, $domain = null, $inject = null)
        {
                if (!isset($attrib) || $attrib == false) {
                        $attrib = DirectoryManager::DEFAULT_SEARCH_ATTRIB;
                }
                if (!isset($inject) || $inject == false) {
                        $inject = $manager->getFilter();
                }
                if ($attrib == UserPrincipal::ATTR_PN) {
                        $domain = $manager->getRealm($needle);
                }
                if (!is_array($inject)) {
                        $inject = array($inject);
                }
                
                $this->_needle = $needle;
                $this->_attrib = $attrib;
                $this->_domain = $domain;
                $this->_inject = $inject;
        }

        /**
         * Get search attribute.
         * @return string
         */
        public function getAttribute()
        {
                return $this->_attrib;
        }

        /**
         * Set search attribute.
         * @param string $attrib The search attribute.
         */
        public function setAttribute($attrib)
        {
                $this->_attrib = $attrib;
        }

        /**
         * Set search string.
         * @param string $needle The search string.
         */
        public function setNeedle($needle)
        {
                $this->_needle = $needle;
        }

        /**
         * Get search domain.
         * @return string
         */
        public function getDomain()
        {
                return $this->_domain;
        }

        /**
         * Set search domain.
         * @param string $domain The search domain.
         */
        public function setDomain($domain)
        {
                $this->_domain = $domain;
        }

        /**
         * Get attributes filter.
         * @return array
         */
        public function getFilter()
        {
                return $this->_inject;
        }

        /**
         * Set attributes filter.
         * @param array $attributes The returned attributes.
         */
        public function setFilter($attributes)
        {
                $this->_inject = $attributes;
        }

        /**
         * Get directory manager search result.
         * @param DirectoryManager $manager The directory manager.
         * @return string|array
         */
        public function getResult($manager)
        {
                foreach ($manager->getServices($this->_domain) as $name => $service) {
                        if (($result = $this->getPrincipal($manager, $service, $name)) != null) {
                                return $result;
                        }
                }
        }

        /**
         * Get directory principals.
         * 
         * @param DirectoryManager $manager The directory manager.
         * @param DirectoryService $service The directory service.
         * @param string $name The service name.
         * @return array
         */
        private function getPrincipal($manager, $service, $name)
        {
                try {
                        return $service->getPrincipal($this->_needle, $this->_attrib, $this->_domain, $this->_inject);
                } catch (Exception $exception) {
                        $manager->report($exception, $service, $name);
                }
        }

}
