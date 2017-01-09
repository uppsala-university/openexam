<?php

// 
// The source code is copyrighted, with equal shared rights, between the
// authors (see the file AUTHORS) and the OpenExam project, Uppsala University 
// unless otherwise explicit stated elsewhere.
// 
// File:    Staff.php
// Created: 2016-04-29 02:30:30
// 
// Author:  Anders Lövgren (QNET/BMC CompDept)
// 

namespace OpenExam\Library\Core\Exam;

use OpenExam\Library\Catalog\Principal;
use OpenExam\Library\Security\Roles;
use OpenExam\Models\Exam;
use Phalcon\Mvc\User\Component;

/**
 * Information about exam staff.
 * 
 * Provides basic information about staff working on an exam. For finer 
 * grained details, use the model instead. 
 * 
 * The data maintained by this class is organized as two-dimensional array
 * where the first dimension is keyed by role (creator, contributor, corrector,
 * decoder or invigilator) and and the second is keyed by their username.
 * 
 * The user data is just name and email address.
 * 
 * @property-read array $contributors Get all contributors.
 * @property-read array $correctors Get all correctors.
 * @property-read array $creators Get all creators.
 * @property-read array $decoders Get all decoders.
 * @property-read array $invigilators Get all invigilators.
 * 
 * @author Anders Lövgren (QNET/BMC CompDept)
 */
class Staff extends Component
{

        /**
         * @var Exam 
         */
        private $_exam;
        /**
         * The staff data.
         * @var array 
         */
        private $_data;
        /**
         * Cache key.
         * @var string
         */
        private $_cachekey;
        /**
         * Cache data lifetime.
         * @var int 
         */
        private $_lifetime;

        /**
         * Constructor.
         * @param Exam $exam The exam object.
         */
        public function __construct($exam)
        {
                $this->_exam = $exam;
                $this->_cachekey = $this->createCacheKey();
                $this->_lifetime = 24 * 3600;
                $this->setData();
        }
        
        /**
         * Destructor.
         */
        public function __destruct()
        {
                unset($this->_cachekey);
                unset($this->_data);
                unset($this->_exam);
        }

        public function __isset($property)
        {
                if (isset($this->_data[$property])) {
                        return count($this->_data[$property]) != 0;
                }
        }

        public function __get($property)
        {
                if (isset($this->_data[$property])) {
                        return $this->_data[$property];
                } else {
                        return parent::__get($property);
                }
        }

        /**
         * Get staff data.
         * @return array
         */
        public function getData($role = null, $user = null)
        {
                if (!isset($role)) {
                        return $this->_data;
                } elseif (!isset($user)) {
                        return $this->_data[$role];
                } else {
                        return $this->_data[$role][$user];
                }
        }

        /**
         * Get all roles.
         * @return array
         */
        public function getRoles()
        {
                return array_keys($this->_data);
        }

        /**
         * Get all users having role.
         * @param string $role The role name.
         * @return array
         */
        public function getUsers($role)
        {
                return array_keys($this->_data[$role]);
        }

        /**
         * Check if role has data.
         * @param string $role The role name.
         * @return boolean
         */
        public function hasRole($role)
        {
                return count($this->_data[$role]) != 0;
        }

        private function createCacheKey()
        {
                return sprintf("staff-exam-%d", $this->_exam->id);
        }

        private function setData()
        {
                if ($this->cache->exists($this->_cachekey, $this->_lifetime)) {
                        $this->_data = $this->cache->get($this->_cachekey);
                } else {
                        $this->_data = $this->getStaff();
                        $this->cache->save($this->_cachekey, $this->_data, $this->_lifetime);
                }
        }

        /**
         * Get staff data.
         * @return array
         */
        private function getStaff()
        {
                $staff = array(
                        Roles::CREATOR     => array(),
                        Roles::INVIGILATOR => array(),
                        Roles::CONTRIBUTOR => array(),
                        Roles::DECODER     => array(),
                        Roles::CORRECTOR   => array()
                );

                foreach ($this->_exam->contributors as $role) {
                        $staff[Roles::CONTRIBUTOR][$role->user] = array(
                                'name' => $role->name,
                                'mail' => $role->mail
                        );
                }

                foreach ($this->_exam->decoders as $role) {
                        $staff[Roles::DECODER][$role->user] = array(
                                'name' => $role->name,
                                'mail' => $role->mail
                        );
                }

                foreach ($this->_exam->invigilators as $role) {
                        $staff[Roles::INVIGILATOR][$role->user] = array(
                                'name' => $role->name,
                                'mail' => $role->mail
                        );
                }

                foreach ($this->_exam->questions as $question) {
                        foreach ($question->correctors as $role) {
                                if (!in_array($role->user, $staff[Roles::CORRECTOR])) {
                                        $staff[Roles::CORRECTOR][$role->user] = array(
                                                'name' => $role->name,
                                                'mail' => $role->mail
                                        );
                                }
                        }
                }

                $principal = $this->catalog->getPrincipal(
                    $this->_exam->creator, Principal::ATTR_PN, array(
                        'attr' => array(
                                Principal::ATTR_NAME,
                                Principal::ATTR_MAIL
                        )
                ));
                $staff[Roles::CREATOR][$this->_exam->creator] = array(
                        'name' => $principal->name,
                        'mail' => $principal->mail
                );

                return $staff;
        }

}
