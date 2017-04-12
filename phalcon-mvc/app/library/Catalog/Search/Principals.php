<?php

// 
// The source code is copyrighted, with equal shared rights, between the
// authors (see the file AUTHORS) and the OpenExam project, Uppsala University 
// unless otherwise explicit stated elsewhere.
// 
// File:    Principals.php
// Created: 2017-04-12 00:10:14
// 
// Author:  Anders Lövgren (QNET/BMC CompDept)
// 

namespace OpenExam\Library\Catalog\Search;

use OpenExam\Library\Catalog\DirectoryManager;
use OpenExam\Library\Catalog\Exception;
use OpenExam\Library\Catalog\Principal as UserPrincipal;

/**
 * Directory principals search.
 *
 * @author Anders Lövgren (QNET/BMC CompDept)
 */
class Principals implements ManagerSearch
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
         * Miscellanous search options
         * @var array 
         */
        private $_options;

        /**
         * Constructor.
         * 
         * @param DirectoryManager $manager The directory manager.
         * @param string $needle The attribute search string.
         * @param string $attrib The attribute to query (optional).
         * @param array $options Miscellanous search options (optional).
         */
        public function __construct($manager, $needle, $attrib = null, $options = null)
        {
                if (!isset($attrib) || $attrib == false) {
                        $attrib = DirectoryManager::DEFAULT_SEARCH_ATTRIB;
                }
                if (!isset($options) || $options == false) {
                        $options = array();
                }
                if (!isset($options['attr']) || $options['attr'] == false) {
                        $options['attr'] = DirectoryManager::$DEFAULT_RESULT_ATTR_LIST;
                }
                if (!isset($options['limit']) || $options['limit'] == false) {
                        $options['limit'] = DirectoryManager::DEFAULT_RESULT_LIMIT;
                }
                if ($attrib == UserPrincipal::ATTR_PN) {
                        $options['domain'] = $manager->getDomain($needle);
                }
                if (!is_array($options['attr'])) {
                        $options['attr'] = array($options['attr']);
                }

                $this->_needle = $needle;
                $this->_attrib = $attrib;
                $this->_options = $options;
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
         * Get search options.
         * @return array
         */
        public function getOptions()
        {
                return $this->_options;
        }

        /**
         * Set search domain.
         * @param string $domain The search domain.
         */
        public function setDomain($domain)
        {
                $this->_options['domain'] = $domain;
        }

        /**
         * Set attributes filter.
         * @param array $attributes The returned attributes.
         */
        public function setFilter($attributes)
        {
                $this->_options['attr'] = $attributes;
        }

        /**
         * Get directory manager search result.
         * @param DirectoryManager $manager The directory manager.
         * @return string|array
         */
        public function getResult($manager)
        {
                $result = array();
                $domain = $this->_options['domain'];

                $limit = $this->_options['limit'];

                foreach ($manager->getServices($domain) as $name => $service) {
                        if (($principals = $this->getPrincipals($manager, $service, $name)) != null) {
                                if ($limit == 0) {
                                        $result = array_merge($result, $principals);
                                } elseif (count($principals) + count($result) < $limit) {
                                        $result = array_merge($result, $principals);
                                } else {
                                        $insert = $limit - count($result);
                                        $result = array_merge($result, array_slice($principals, 0, $insert));
                                        return $result;
                                }
                        }
                }

                return $result;
        }

        /**
         * Get directory principals.
         * 
         * @param DirectoryManager $manager The directory manager.
         * @param DirectoryService $service The directory service.
         * @param string $name The service name.
         * @return array
         */
        private function getPrincipals($manager, $service, $name)
        {
                try {
                        return $service->getPrincipals($this->_needle, $this->_attrib, $this->_options);
                } catch (Exception $exception) {
                        $manager->report($exception, $service, $name);
                }
        }

}
