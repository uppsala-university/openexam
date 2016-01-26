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

use OpenExam\Library\Catalog\DirectoryManager;
use OpenExam\Library\Catalog\Principal;
use OpenExam\Models\ModelBase;
use Phalcon\Mvc\Model\Validator\Uniqueness;

/**
 * Base class for all role models.
 *
 * @author Anders Lövgren (QNET/BMC CompDept)
 */
class Role extends ModelBase
{

        /**
         * The directory service (catalog service).
         * @var DirectoryManager 
         */
        protected $catalog;
        /**
         * The user name.
         * @var string 
         */
        public $name;
        /**
         * The email address.
         * @var string 
         */
        public $mail;

        protected function initialize()
        {
                parent::initialize();
                $this->catalog = $this->getDI()->getCatalog();
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
                return $this->validationHasFailed() != true;
        }

        /**
         * Called after the model was read.
         */
        protected function afterFetch()
        {
                $this->name = $this->getAttribute(Principal::ATTR_NAME);
                $this->mail = $this->getAttribute(Principal::ATTR_MAIL);
                parent::afterFetch();
        }

        /**
         * Called after the model was deleted.
         */
        protected function afterDelete()
        {
                $cachekey = sprintf("roles-%s", $this->user);
                
                if ($this->getDI()->get('cache')->exists($cachekey)) {
                        $this->getDI()->get('cache')->delete($cachekey);
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
                if (($attrs = $this->catalog->getAttribute($this->user, $name))) {
                        return current($attrs)[$name][0];
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
