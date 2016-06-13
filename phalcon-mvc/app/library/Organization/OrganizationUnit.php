<?php

// 
// The source code is copyrighted, with equal shared rights, between the
// authors (see the file AUTHORS) and the OpenExam project, Uppsala University 
// unless otherwise explicit stated elsewhere.
// 
// File:    OrganizationUnit.php
// Created: 2016-05-13 02:40:44
// 
// Author:  Anders Lövgren (QNET/BMC CompDept)
// 

namespace OpenExam\Library\Organization;

use OpenExam\Library\Organization\DataProvider\Exams as ExamsDataProvider;
use OpenExam\Library\Organization\DataProvider\Roles as RolesDataProvider;
use OpenExam\Library\Organization\DataProvider\Users as UsersDataProvider;
use Phalcon\Mvc\User\Component;

/**
 * Abstract base class for all kind of organization units (divisions, 
 * departments or groups).
 *
 * @author Anders Lövgren (QNET/BMC CompDept)
 */
abstract class OrganizationUnit extends Component
{

        /**
         * Default lifetime for cache entry.
         */
        const CACHE_ENTRY_LIFETIME = 86400;

        /**
         * The organization unit name.
         * @var string 
         */
        protected $_name;
        /**
         * Cached data.
         * @var array 
         */
        protected $_data;
        /**
         * The cache key.
         * @var string 
         */
        private $_cachekey;
        /**
         * The cache entry lifetime.
         * @var int 
         */
        private $_lifetime;

        /**
         * Constructor.
         * @param string $name The organization unit name.
         * @param int $lifetime The cache entry lifetime.
         */
        public function __construct($name, $lifetime = self::CACHE_ENTRY_LIFETIME)
        {
                $this->_name = $name;
                $this->_cachekey = $this->createCacheKey();
                $this->_lifetime = $lifetime;

                $this->setData();
        }

        /**
         * Get number of entries in cached data.
         * @return int
         */
        public function count()
        {
                return array_keys($this->_data);
        }

        /**
         * Get organization unit name.
         * @return string
         */
        public function getName()
        {
                return $this->_name;
        }

        /**
         * Check if cached data exist.
         * @return boolean
         */
        public function hasData()
        {
                return isset($this->_data);
        }

        /**
         * Get cached data.
         * @return array
         */
        public function getData()
        {
                return $this->_data;
        }

        /**
         * Set cached data.
         */
        private function setData()
        {
                if ($this->cache->exists($this->_cachekey, $this->_lifetime)) {
                        $this->_data = $this->cache->get($this->_cachekey, $this->_lifetime);
                        return;
                }

                if (($this->_data = $this->findData())) {
                        $this->cache->save($this->_cachekey, $this->_data, $this->_lifetime);
                }
        }

        /**
         * Find data.
         * 
         * Sub classes could override this method to return data that 
         * should be cached for their organization unit. If no data should
         * be cached, then return null.
         * 
         * Returns array on this form:
         * <code>
         * array(
         *      'name'  => string,      // Organization unit name
         *      'exams' => int,         // The number of exams
         *      'users' => int,         // The number of users
         *      'roles' => int          // The number of roles
         * )
         * </code>
         * 
         * The number of users are distinct users having at least one role
         * on one of the exams belonging to this organization unit.
         * 
         * @return array
         */
        protected function findData()
        {
                $exams = $this->getExams();
                $roles = $this->getRoles();
                $users = $this->getUsers();

                return array(
                        'name'  => $this->_name,
                        'exams' => $exams->getSize(),
                        'users' => $users->getSize(),
                        'roles' => $roles->getSize()
                );
        }

        /**
         * Create cache key.
         * @return string
         */
        protected abstract function createCacheKey();

        /**
         * Get exams for this organization unit.
         * @return ExamsDataProvider
         */
        public abstract function getExams();

        /**
         * Get roles for this organization unit.
         * @return RolesDataProvider
         */
        public abstract function getRoles();

        /**
         * Get users for this organization unit.
         * @return UsersDataProvider
         */
        public abstract function getUsers();

        /**
         * Check if this object might have child object.
         * @return boolean 
         */
        public abstract function hasChildren();

        /**
         * Get child object.
         * @return array 
         */
        public abstract function getChildren();
}
