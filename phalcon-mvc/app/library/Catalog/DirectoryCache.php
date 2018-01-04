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
// File:    DirectoryCache.php
// Created: 2017-01-09 05:18:23
// 
// Author:  Anders Lövgren (QNET/BMC CompDept)
// 

namespace OpenExam\Library\Catalog;

use Phalcon\Cache\BackendInterface;
use Phalcon\Mvc\User\Component;

/**
 * Cache for the directory service.
 *
 * @author Anders Lövgren (QNET/BMC CompDept)
 */
class DirectoryCache extends Component implements DirectoryQuery
{

        /**
         * The cache backend.
         * @var BackendInterface 
         */
        private $_cache;

        /**
         * Constructor.
         * @param BackendInterface $cache The cache backend.
         */
        public function __construct($cache = null)
        {
                if (isset($cache)) {
                        $this->_cache = $cache;
                } else {
                        $this->_cache = $this->cache;
                }
        }

        /**
         * Set cache backend.
         * @param BackendInterface $cache The cache backend.
         */
        public function setBackend($cache)
        {
                $this->_cache = $cache;
        }

        public function getServiceName()
        {
                return 'cache';
        }

        public function getAttribute($attribute, $principal = null)
        {
                $cachekey = sprintf("catalog-%s-attribute-%s-%s", $this->getServiceName(), $attribute, md5($principal));
                return $this->getContent($cachekey);
        }

        public function setAttribute($attribute, $principal, &$content)
        {
                $cachekey = sprintf("catalog-%s-attribute-%s-%s", $this->getServiceName(), $attribute, md5($principal));
                $this->setContent($cachekey, $content);
        }

        public function getAttributes($attribute, $principal = null)
        {
                $cachekey = sprintf("catalog-%s-attributes-%s-%s", $this->getServiceName(), $attribute, md5($principal));
                return $this->getContent($cachekey);
        }

        public function setAttributes($attribute, $principal, &$content)
        {
                $cachekey = sprintf("catalog-%s-attributes-%s-%s", $this->getServiceName(), $attribute, md5($principal));
                $this->setContent($cachekey, $content);
        }

        public function getGroups($principal, $attributes = null)
        {
                $cachekey = sprintf("catalog-%s-groups-%s", $this->getServiceName(), md5(serialize(array($principal, $attributes))));
                return $this->getContent($cachekey);
        }

        public function setGroups($principal, $attributes, &$content)
        {
                $cachekey = sprintf("catalog-%s-groups-%s", $this->getServiceName(), md5(serialize(array($principal, $attributes))));
                $this->setContent($cachekey, $content);
        }

        public function getMembers($group, $domain = null, $attributes = null)
        {
                $cachekey = sprintf("catalog-%s-members-%s", $this->getServiceName(), md5(serialize(array($group, $domain, $attributes))));
                return $this->getContent($cachekey);
        }

        public function setMembers($group, $domain, $attributes, &$content)
        {
                $cachekey = sprintf("catalog-%s-members-%s", $this->getServiceName(), md5(serialize(array($group, $domain, $attributes))));
                $this->setContent($cachekey, $content);
        }

        public function getPrincipal($needle, $search = null, $domain = null, $attr = null)
        {
                $cachekey = sprintf("catalog-%s-principal-%s-%s-%s", $this->getServiceName(), $search, $domain, md5(serialize(array($needle, $attr))));
                return $this->getContent($cachekey);
        }

        public function setPrincipal($needle, $search, $domain, $attr, &$content)
        {
                $cachekey = sprintf("catalog-%s-principal-%s-%s-%s", $this->getServiceName(), $search, $domain, md5(serialize(array($needle, $attr))));
                $this->setContent($cachekey, $content);
        }

        public function getPrincipals($needle, $search = null, $options = null)
        {
                $cachekey = sprintf("catalog-%s-principals-%s-%s", $this->getServiceName(), $search, md5(serialize(array($needle, $options))));
                return $this->getContent($cachekey);
        }

        public function setPrincipals($needle, $search, $options, &$content)
        {
                $cachekey = sprintf("catalog-%s-principals-%s-%s", $this->getServiceName(), $search, md5(serialize(array($needle, $options))));
                $this->setContent($cachekey, $content);
        }

        /**
         * Get cache value or false if missing.
         * 
         * @param string $cachekey The cache key.
         * @return mixed 
         */
        private function getContent($cachekey)
        {
                if ($this->_cache->exists($cachekey)) {
                        return $this->_cache->get($cachekey);
                } else {
                        return false;
                }
        }

        /**
         * Set cache value.
         * 
         * @param string $cachekey The cache key.
         * @param mixed $content The cache value.
         */
        private function setContent($cachekey, $content)
        {
                $this->_cache->save($cachekey, $content);
        }

}
