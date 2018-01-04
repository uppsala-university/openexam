<?php

/*
 * Copyright (C) 2016-2018 The OpenExam Project
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
// File:    Students.php
// Created: 2016-05-18 22:24:24
// 
// Author:  Anders Lövgren (QNET/BMC CompDept)
// 

namespace OpenExam\Library\Organization\DataProvider;

use Phalcon\Mvc\User\Component;

/**
 * Students data provider.
 *
 * @author Anders Lövgren (QNET/BMC CompDept)
 */
class Students extends Component
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
         * Destructor.
         */
        public function __destruct()
        {
                unset($this->_data);
                unset($this->_users);
        }

        /**
         * Get number of students.
         * @return int
         */
        public function getSize()
        {
                $this->setUsers();
                return count($this->_data);
        }

        /**
         * Get students data.
         * @return array
         */
        public function getData()
        {
                $this->setUsers();
                return $this->_data;
        }

        /**
         * Get object name (translated).
         * @return string
         */
        public function getName()
        {
                return $this->tr->_('Students');
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
         * Set users data.
         */
        private function setUsers()
        {
                if (!isset($this->_data)) {
                        $this->_data = array_filter($this->_users->getData(), function($user) {
                                if ($user['type'] == Users::TYPE_STUDENT) {
                                        return $user;
                                }
                        });
                }
        }

}
