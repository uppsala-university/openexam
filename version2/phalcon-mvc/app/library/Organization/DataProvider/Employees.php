<?php

// 
// The source code is copyrighted, with equal shared rights, between the
// authors (see the file AUTHORS) and the OpenExam project, Uppsala University 
// unless otherwise explicit stated elsewhere.
// 
// File:    Employees.php
// Created: 2016-05-18 22:25:00
// 
// Author:  Anders Lövgren (QNET/BMC CompDept)
// 

namespace OpenExam\Library\Organization\DataProvider;

/**
 * Employees data provider.
 *
 * @author Anders Lövgren (QNET/BMC CompDept)
 */
class Employees
{

        /**
         * The users data provider.
         * @var Users 
         */
        private $_users;
        /**
         * The users array.
         * @var array 
         */
        private $_data;

        /**
         * Constructor.
         * @param Users $users The users data provider.
         */
        public function __construct($users)
        {
                $this->_users = $users;
        }

        /**
         * Get number of employees.
         * @return int
         */
        public function getSize()
        {
                return count($this->getUsers());
        }

        /**
         * Get employees data.
         * @return array
         */
        public function getData()
        {
                return $this->getUsers();
        }

        /**
         * Get user data provider.
         * @return Users
         */
        public function getProvider()
        {
                return $this->_users;
        }

        /**
         * Get users data.
         */
        private function getUsers()
        {
                if (!isset($this->_data)) {
                        $this->_data = array_filter($this->_users->getData(), function($data) {
                                if ($data['type'] == Users::TYPE_EMPLOYEE) {
                                        return $data;
                                }
                        });
                }

                return $this->_data;
        }

}
