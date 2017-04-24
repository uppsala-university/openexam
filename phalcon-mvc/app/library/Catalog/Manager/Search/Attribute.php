<?php

// 
// The source code is copyrighted, with equal shared rights, between the
// authors (see the file AUTHORS) and the OpenExam project, Uppsala University 
// unless otherwise explicit stated elsewhere.
// 
// File:    Attribute.php
// Created: 2017-04-11 23:33:56
// 
// Author:  Anders Lövgren (QNET/BMC CompDept)
// 

namespace OpenExam\Library\Catalog\Manager\Search;

use OpenExam\Library\Catalog\DirectoryManager;
use OpenExam\Library\Catalog\DirectoryService;
use OpenExam\Library\Catalog\Exception;
use OpenExam\Library\Catalog\Manager\Search;
use OpenExam\Library\Catalog\Principal as UserPrincipal;
use Phalcon\Mvc\User\Component;

/**
 * Directory attribute search.
 *
 * @author Anders Lövgren (QNET/BMC CompDept)
 */
class Attribute extends Component implements Search
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
                        if (($attributes = $this->getAttribute($manager, $service, $name)) != null) {
                                if (is_array($attributes)) {
                                        $result = array_merge($result, $attributes);
                                } elseif (is_string($attributes)) {
                                        $result = $attributes;
                                        break;
                                }
                        }
                }

                if (count($result) == 0) {
                        $result = null;
                }
                if (is_array($result)) {
                        $result = array_unique($result);
                }

                // 
                // Fix for attributes having multiple values:
                // 
                if ($this->_attribute == UserPrincipal::ATTR_AFFIL ||
                    $this->_attribute == UserPrincipal::ATTR_ASSUR) {
                        if (is_null($result)) {
                                $result = array();
                        }
                }

                return $result;
        }

        /**
         * Get directory attribute.
         * 
         * @param DirectoryManager $manager The directory manager.
         * @param DirectoryService $service The directory service.
         * @param string $name The service name.
         * @return string|array
         */
        private function getAttribute($manager, $service, $name)
        {
                try {
                        return $service->getAttribute($this->_attribute, $this->_principal);
                } catch (Exception $exception) {
                        $manager->report($exception, $service, $name);
                }
        }

}
