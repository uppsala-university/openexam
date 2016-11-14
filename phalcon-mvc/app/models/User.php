<?php

// 
// The source code is copyrighted, with equal shared rights, between the
// authors (see the file AUTHORS) and the OpenExam project, Uppsala University 
// unless otherwise explicit stated elsewhere.
// 
// File:    Topic.php
// Created: 2014-02-24 07:04:58
// 
// Author:  Anders Lövgren (Computing Department at BMC, Uppsala University)
// 

namespace OpenExam\Models;

use OpenExam\Library\Core\Pattern;
use Phalcon\Mvc\Model\Validator\Regex as RegexValidator;

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

        public function validation()
        {
                $this->validate(new RegexValidator(
                    array(
                        "field"   => "principal",
                        "message" => "The username '$this->principal' is not matching expected format",
                        "pattern" => Pattern::USER
                    )
                ));

                return $this->validationHasFailed() != true;
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
                        'source'       => 'source'
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