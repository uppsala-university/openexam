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
// File:    Role.php
// Created: 2014-11-10 23:20:49
// 
// Author:  Anders Lövgren (QNET/BMC CompDept)
// 

namespace OpenExam\Models;

use OpenExam\Library\Catalog\DirectoryService;
use OpenExam\Library\Catalog\Principal;
use OpenExam\Library\Core\Pattern;
use OpenExam\Library\Model\Behavior\Transform\Trim;
use OpenExam\Models\ModelBase;
use Phalcon\Validation;
use Phalcon\Validation\Validator\Regex as RegexValidator;
use Phalcon\Validation\Validator\Uniqueness;

/**
 * Base class for all role models.
 *
 * @author Anders Lövgren (QNET/BMC CompDept)
 */
class Role extends ModelBase
{

        /**
         * The user name.
         * @var string 
         */
        public $name;
        /**
         * The email address.
         * @var array 
         */
        public $mail = array();
        /**
         * The first name.
         * @var string 
         */
        public $fname;
        /**
         * The last name.
         * @var string 
         */
        public $lname;
        /**
         * The display name (real name or user).
         * @var string 
         */
        public $display;

        protected function initialize()
        {
                parent::initialize();

                $this->addBehavior(new Trim(array(
                        'beforeValidationOnCreate' => array(
                                'field' => "user",
                                'value' => null
                        ),
                        'beforeValidationOnUpdate' => array(
                                'field' => "user",
                                'value' => null
                        )
                )));
        }

        public function validation()
        {
                if (defined('VALIDATION_SKIP_UNIQUENESS_CHECK')) {
                        return true;
                }

                $validator = new Validation();

                if (property_exists($this, 'exam_id')) {
                        $validator->add(
                            array(
                                "user", "exam_id"
                            ), new Uniqueness(
                            array(
                                "message" => "This role has already been granted for $this->user"
                            )
                        ));
                } elseif (property_exists($this, 'question_id')) {
                        $validator->add(
                            array(
                                "user", "question_id"
                            ), new Uniqueness(
                            array(
                                "message" => "This role has already been granted for $this->user"
                            )
                        ));
                } else {
                        $validator->add(
                            "user", new Uniqueness(
                            array(
                                "message" => "This role has already been granted for $this->user"
                            )
                        ));
                }

                $validator->add(
                    "user", new RegexValidator(
                    array(
                        "message" => "The username '$this->user' is not matching expected format",
                        "pattern" => Pattern::get(Pattern::MATCH_USER)
                    )
                ));

                return $this->validate($validator);
        }

        /**
         * Called after the model was read.
         */
        protected function afterFetch()
        {
                $this->setAttributes();
                parent::afterFetch();
        }

        /**
         * Called after the model was created.
         */
        protected function afterCreate()
        {
                $this->setAttributes();
        }

        /**
         * Called after the model was deleted.
         */
        protected function afterDelete()
        {
                $key = sprintf("roles-%s", $this->user);

                if (($cache = $this->getDI()->getCache()) != false) {
                        if ($cache->exists($key)) {
                                $cache->delete($key);
                        }
                }
        }

        /**
         * Called before validation of the model object.
         */
        protected function beforeValidation()
        {
                $this->setUserDomain();
                $this->setUserNormalized();
        }

        /**
         * Helper function returning single line attribute from catalog service.
         * @param string $name The attribute name.
         * @return string
         */
        protected function getAttribute($name)
        {
                if (($catalog = $this->getDI()->getCatalog()) != false) {
                        return $catalog->getAttribute($name, $this->user);
                }
        }

        /**
         * Get all user principals for this user from directory service.
         * @return array
         */
        protected function getAttributes()
        {
                if (($catalog = $this->getDI()->getCatalog()) != false) {
                        return $catalog->getPrincipals(
                                $this->user, Principal::ATTR_PN, array(
                                    'attr' => array(
                                            Principal::ATTR_NAME,
                                            Principal::ATTR_GN,
                                            Principal::ATTR_SN,
                                            Principal::ATTR_MAIL
                                    )
                        ));
                }
        }

        /**
         * Set all attributes from directory service.
         */
        protected function setAttributes()
        {
                if (!($principals = $this->getAttributes())) {
                        $this->display = $this->user;
                        return;
                }

                foreach ($principals as $principal) {
                        if (!isset($this->name) && isset($principal->name)) {
                                $this->name = $principal->name;
                        }
                        if (!isset($this->fname) && isset($principal->gn)) {
                                $this->fname = $principal->gn;
                        }
                        if (!isset($this->lname) && isset($principal->sn)) {
                                $this->lname = $principal->sn;
                        }
                        if (isset($principal->mail)) {
                                if (is_string($principal->mail)) {
                                        $principal->mail = array($principal->mail);
                                }
                                foreach ($principal->mail as $key => $val) {
                                        if (!in_array($val, $this->mail)) {
                                                $this->mail[] = $val;
                                        }
                                        if (!is_numeric($key)) {
                                                $this->mail[$key] = $val;
                                        }
                                }
                        }
                }

                if (count($this->mail) != 0) {
                        $this->setPrimaryMail(DirectoryService::PRIMARY_ATTR_VALUE);
                }
                if (isset($this->name)) {
                        $this->display = $this->name;
                } else {
                        $this->display = $this->user;
                }
        }

        /**
         * Set primary mail for user.
         * @param string $key The primary mail attribute key.
         */
        private function setPrimaryMail($key, $unique = true)
        {
                if (array_key_exists($key, $this->mail)) {
                        array_unshift($this->mail, $this->mail[$key]);
                }
                if ($unique) {
                        $this->mail = array_unique($this->mail);
                }
        }

        /**
         * Apply default domain to user principal name if missing.
         */
        private function setUserDomain()
        {
                while ($this->user[0] == '@') {         // Someones gonna try...
                        $this->user = substr($this->user, 1);
                }

                if (is_numeric($this->user[0])) {
                        return;
                } elseif (strpos($this->user, '@') > 0) {
                        return;
                }

                if (($config = $this->getDI()->getConfig()) == false) {
                        $domain = $this->getDI()->getUser()->getDomain();
                        $this->user = sprintf("%s@%s", $this->user, $domain);
                } elseif (($domain = $config->user->domain) != false) {
                        $this->user = sprintf("%s@%s", $this->user, $domain);
                }
        }

        /**
         * Set normalized username.
         */
        private function setUserNormalized()
        {
                if (($normalizer = $this->getDI()->getAuth()->getNormalizer())) {
                        $this->user = call_user_func($normalizer, $this->user);
                }
        }

}
