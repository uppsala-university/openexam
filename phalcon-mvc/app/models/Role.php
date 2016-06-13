<?php

// 
// The source code is copyrighted, with equal shared rights, between the
// authors (see the file AUTHORS) and the OpenExam project, Uppsala University 
// unless otherwise explicit stated elsewhere.
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
use OpenExam\Models\ModelBase;
use Phalcon\Mvc\Model\Validator\Uniqueness;
use Phalcon\Mvc\Model\Validator\Regex as RegexValidator;

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

        protected function initialize()
        {
                parent::initialize();
        }

        public function validation()
        {
                if (defined('VALIDATION_SKIP_UNIQUENESS_CHECK')) {
                        return true;
                }

                if (property_exists($this, 'exam_id')) {
                        $this->validate(new Uniqueness(
                            array(
                                "field"   => array("user", "exam_id"),
                                "message" => "This role has already been granted for $this->user"
                            )
                        ));
                } elseif (property_exists($this, 'question_id')) {
                        $this->validate(new Uniqueness(
                            array(
                                "field"   => array("user", "question_id"),
                                "message" => "This role has already been granted for $this->user"
                            )
                        ));
                } else {
                        $this->validate(new Uniqueness(
                            array(
                                "field"   => "user",
                                "message" => "This role has already been granted for $this->user"
                            )
                        ));
                }

                $this->validate(new RegexValidator(
                    array(
                        "field"   => "user",
                        "message" => "The username '$this->user' is not matching expected format",
                        "pattern" => Pattern::USER
                    )
                ));

                return $this->validationHasFailed() != true;
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
                $this->setDomain();
        }

        /**
         * Helper function returning single line attribute from catalog service.
         * @param string $name The attribute name.
         * @return string
         */
        protected function getAttribute($name)
        {
                if (($catalog = $this->getDI()->getCatalog()) != false) {
                        if (($attrs = $catalog->getAttribute($this->user, $name))) {
                                return current($attrs)[$name][0];
                        }
                }
        }

        /**
         * Get all attributes from directory service.
         * @return array
         */
        protected function getAttributes()
        {
                if (($catalog = $this->getDI()->getCatalog()) != false) {
                        return $catalog->getPrincipal(
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

                $this->setPrimaryMail(DirectoryService::PRIMARY_ATTR_VALUE);
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
        private function setDomain()
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

}
