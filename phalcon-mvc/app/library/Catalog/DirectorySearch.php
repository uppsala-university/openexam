<?php

/*
 * Copyright (C) 2017-2018 The OpenExam Project
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
// File:    DirectorySearch.php
// Created: 2017-04-11 22:17:16
// 
// Author:  Anders Lövgren (QNET/BMC CompDept)
// 

namespace OpenExam\Library\Catalog;

use OpenExam\Library\Catalog\Manager\Search\Attribute as ManagerSearchAttribute;
use OpenExam\Library\Catalog\Manager\Search\Attributes as ManagerSearchAttributes;
use OpenExam\Library\Catalog\Manager\Search\Groups as ManagerSearchGroups;
use OpenExam\Library\Catalog\Manager\Search\Members as ManagerSearchMembers;
use OpenExam\Library\Catalog\Manager\Search\Principal as ManagerSearchPrincipal;
use OpenExam\Library\Catalog\Manager\Search\Principals as ManagerSearchPrincipals;

/**
 * Directory manager search.
 *
 * @author Anders Lövgren (QNET/BMC CompDept)
 */
class DirectorySearch implements DirectoryQuery
{

        /**
         * The directory manager.
         * @var DirectoryManager 
         */
        private $_manager;

        /**
         * Constructor.
         * @param DirectoryManager $manager The directory manager.
         */
        public function __construct($manager)
        {
                $this->_manager = $manager;
        }

        /**
         * Set directory manager.
         * @param DirectoryManager $manager The directory manager.
         */
        public function setManager($manager)
        {
                $this->_manager = $manager;
        }

        /**
         * Get groups for user.
         * 
         * @param string $principal The user principal name.
         * @param array $attributes The attributes to return.
         * @return array
         */
        public function getGroups($principal, $attributes = null)
        {
                $search = new ManagerSearchGroups($principal, $attributes);
                $result = $search->getResult($this->_manager);

                return $result;
        }

        /**
         * Get members of group.
         * 
         * @param string $group The group name.
         * @param string $domain Restrict search to domain.
         * @param array $attributes The attributes to return.
         * @return Principal[]
         */
        public function getMembers($group, $domain = null, $attributes = null)
        {
                $search = new ManagerSearchMembers($group, $domain, $attributes);
                $result = $search->getResult($this->_manager);

                return $result;
        }

        /**
         * Get single attribute.
         * 
         * The $principal defaults to caller if unset. The attribute returned
         * is bare (no service reference) and is string for simple attributes
         * like name or mail, but array for more complex attrinutes as
         * affiliation.
         * 
         * @param string $attribute The attribute to return.
         * @param string $principal The user principal name.
         * @return string|array
         */
        public function getAttribute($attribute, $principal = null)
        {
                $search = new ManagerSearchAttribute($attribute, $principal);
                $result = $search->getResult($this->_manager);

                return $result;
        }

        /**
         * Get multiple attributes.
         * 
         * The $principal defaults to caller if unset. The attributes returned
         * comes from all applicable directory services (the ones that handles
         * the user principal domain) and contains service references.
         * 
         * @param string $attribute The attribute to return.
         * @param string $principal The user principal name.
         * @return array
         */
        public function getAttributes($attribute, $principal = null)
        {
                $search = new ManagerSearchAttributes($attribute, $principal);
                $result = $search->getResult($this->_manager);

                return $result;
        }

        /**
         * Get multiple user principal objects.
         * 
         * @param string $needle The attribute search string.
         * @param string $attrib The attribute to query (optional).
         * @param array $options Various search options (optional).
         * 
         * @return Principal[] Matching user principal objects.
         */
        public function getPrincipals($needle, $attrib = null, $options = null)
        {
                $search = new ManagerSearchPrincipals($this->_manager, $needle, $attrib, $options);
                $result = $search->getResult($this->_manager);

                return $result;
        }

        /**
         * Get single user principal object.
         * 
         * @param string $needle The attribute search string.
         * @param string $attrib The attribute to query (optional).
         * @param string $domain The search domain (optional).
         * @param array|string $inject The attributes to return (optional).
         * 
         * @return Principal The matching user principal object.
         */
        public function getPrincipal($needle, $attrib = null, $domain = null, $inject = null)
        {
                $search = new ManagerSearchPrincipal($this->_manager, $needle, $attrib, $domain, $inject);
                $result = $search->getResult($this->_manager);

                return $result;
        }

}
