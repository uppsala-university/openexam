<?php

// 
// The source code is copyrighted, with equal shared rights, between the
// authors (see the file AUTHORS) and the OpenExam project, Uppsala University 
// unless otherwise explicit stated elsewhere.
// 
// File:    Attributes.php
// Created: 2017-04-11 23:51:49
// 
// Author:  Anders Lövgren (QNET/BMC CompDept)
// 

namespace OpenExam\Library\Catalog\Manager\Search;

use OpenExam\Library\Catalog\DirectoryManager;
use OpenExam\Library\Catalog\DirectoryService;
use OpenExam\Library\Catalog\Exception;
use OpenExam\Library\Catalog\Manager\Search;
use Phalcon\Mvc\User\Component;

/**
 * Directory attributes search.
 *
 * @author Anders Lövgren (QNET/BMC CompDept)
 */
class Attributes extends Component implements Search
{

        /**
         * The attribute filter.
         * @var string 
         */
        private $_attribute;
        /**
         * The user principal name.
         * @var string 
         */
        private $_principal;

        /**
         * Constructor.
         * @param string $attribute The attribute filter.
         * @param string $principal The user principal name (defaults to caller).
         */
        public function __construct($attribute, $principal = null)
        {
                if (!isset($principal)) {
                        $principal = $this->user->getPrincipalName();
                }

                $this->_attribute = $attribute;
                $this->_principal = $principal;
        }

        /**
         * Set attribute filter.
         * @param string $attribute The attribute filter.
         */
        public function setFilter($attribute)
        {
                $this->_attribute = $attribute;
        }

        /**
         * Set user principal name.
         * @param string $principal The user principal name.
         */
        public function setPrincipal($principal)
        {
                $this->_principal = $principal;
        }

        /**
         * Get directory manager search result.
         * @param DirectoryManager $manager The directory manager.
         * @return string|array
         */
        public function getResult($manager)
        {
                $domain = $manager->getRealm($this->_principal);
                $result = array();

                foreach ($manager->getServices($domain) as $name => $service) {
                        if (($attributes = $this->getAttributes($manager, $service, $name)) != null) {
                                $result = array_merge($result, $attributes);
                        }
                }

                return $result;
        }

        /**
         * Get directory attributes.
         * 
         * @param DirectoryManager $manager The directory manager.
         * @param DirectoryService $service The directory service.
         * @param string $name The service name.
         * @return array
         */
        private function getAttributes($manager, $service, $name)
        {
                try {
                        return $service->getAttributes($this->_attribute, $this->_principal);
                } catch (Exception $exception) {
                        $manager->report($exception, $service, $name);
                }
        }

}
