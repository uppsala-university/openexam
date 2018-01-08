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
// File:    User.php
// Created: 2016-11-14 22:45:11
// 
// Author:  Anders Lövgren (Computing Department at BMC, Uppsala University)
// 

namespace OpenExam\Models;

use OpenExam\Library\Core\Pattern;
use Phalcon\Validation;
use Phalcon\Validation\Validator\Regex as RegexValidator;

/**
 * The user model.
 * 
 * Represents a system user. This model should not be external exposed. It's 
 * meant to be used internal as catalog source or in authentication. 
 * 
 * @author Anders Lövgren (QNET/BMC CompDept)
 */
class User extends ModelBase
{

        /**
         * The object ID.
         * @var integer
         */
        public $id;
        /**
         * The user principal name.
         * @var string
         */
        public $principal;
        /**
         * The username part of principal name.
         * @var string
         */
        public $uid;
        /**
         * The domain part of principal name.
         * @var string
         */
        public $domain;
        /**
         * The given name.
         * @var string
         */
        public $givenName;
        /**
         * The sir name.
         * @var string
         */
        public $sn;
        /**
         * The display name.
         * @var string
         */
        public $displayName;
        /**
         * The common name (optional).
         * @var string
         */
        public $cn;
        /**
         * The mail address.
         * @var string
         */
        public $mail;
        /**
         * The personal number (optional).
         * @var string
         */
        public $pnr;
        /**
         * The organization name (optional).
         * @var string
         */
        public $o;
        /**
         * The country code (optional), i.e. SE. Must be two char wide.
         * @var string
         */
        public $c;
        /**
         * The country as printable string, i.e. Sweden (optional).
         * @var string
         */
        public $co;
        /**
         * The user home (optional).
         * @var string
         */
        public $home;
        /**
         * The acronym (optional).
         * @var string
         */
        public $acronym;
        /**
         * The user data assurance (optional).
         * @var array
         */
        public $assurance;
        /**
         * The user affiliation (optional).
         * @var array
         */
        public $affiliation;
        /**
         * The user data origin.
         * @var string 
         */
        public $source;
        /**
         * The created timestamp (datetime).
         * @var string 
         */
        public $created;

        public function validation()
        {
                $validator = new Validation();

                $validator->add(
                    "principal", new RegexValidator(
                    array(
                        "message" => "The username '$this->principal' is not matching expected format",
                        "pattern" => Pattern::get(Pattern::MATCH_USER)
                    )
                ));

                return $this->validate($validator);
        }

        /**
         * Get source table name.
         * @return string
         */
        public function getSource()
        {
                return 'users';
        }

        /**
         * Get table column map.
         * @return array
         */
        public function columnMap()
        {
                return array(
                        'id'           => 'id',
                        'principal'    => 'principal',
                        'uid'          => 'uid',
                        'domain'       => 'domain',
                        'given_name'   => 'givenName',
                        'sn'           => 'sn',
                        'display_name' => 'displayName',
                        'cn'           => 'cn',
                        'mail'         => 'mail',
                        'pnr'          => 'pnr',
                        'o'            => 'o',
                        'c'            => 'c',
                        'co'           => 'co',
                        'home'         => 'home',
                        'acronym'      => 'acronym',
                        'assurance'    => 'assurance',
                        'affiliation'  => 'affiliation',
                        'source'       => 'source',
                        'created'      => 'created'
                );
        }

        protected function beforeValidationOnCreate()
        {
                list($this->uid, $this->domain) = explode('@', $this->principal);
        }

        protected function beforeSave()
        {
                $this->affiliation = serialize($this->affiliation);
                $this->assurance = serialize($this->assurance);
        }

        protected function afterSave()
        {
                $this->affiliation = unserialize($this->affiliation);
                $this->assurance = unserialize($this->assurance);
        }

        protected function afterFetch()
        {
                $this->affiliation = unserialize($this->affiliation);
                $this->assurance = unserialize($this->assurance);
        }

}
