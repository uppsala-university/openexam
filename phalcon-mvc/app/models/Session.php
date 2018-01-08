<?php

/*
 * Copyright (C) 2014-2018 The OpenExam Project
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
// File:    Session.php
// Created: 2014-09-20 13:00:59
// 
// Author:  Anders Lövgren (QNET/BMC CompDept)
// 

namespace OpenExam\Models;

use OpenExam\Library\Model\Guard\Session as SessionModelGuard;
use Phalcon\Validation;
use Phalcon\Validation\Validator\Uniqueness;

/**
 * The session model.
 * 
 * This model represents logon sessions.
 * 
 * @author Anders Lövgren (QNET/BMC CompDept)
 */
class Session extends ModelBase
{

        /**
         * Guard against problematic methods use.
         */
        use SessionModelGuard;

        /**
         * The object ID.
         * @var integer
         */
        public $id;
        /**
         * The session ID.
         * @var string
         */
        public $session_id;
        /**
         * The session data.
         * @var string
         */
        public $data;
        /**
         * Timestamp.
         * @var integer
         */
        public $created;
        /**
         * Timestamp.
         * @var integer
         */
        public $updated;

        /**
         * Get source table name.
         * @return string
         */
        public function getSource()
        {
                return 'sessions';
        }

        /**
         * Get table column map.
         * @return array
         */
        public function columnMap()
        {
                return array(
                        'id'         => 'id',
                        'session_id' => 'session_id',
                        'data'       => 'data',
                        'created'    => 'created',
                        'updated'    => 'updated'
                );
        }

        /**
         * Validates business rules.
         * @return boolean
         */
        public function validation()
        {
                $validator = new Validation();
                
                $validator->add(
                    'session_id', new Uniqueness(
                    array(
                        'message' => "The session $this->session_id exists already"
                    )
                ));

                return $this->validate($validator);
        }

}
