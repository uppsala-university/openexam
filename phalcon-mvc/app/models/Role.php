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
use OpenExam\Library\Security\User;
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
                if (property_exists($this, 'exam_id')) {
                        $this->validate(new Uniqueness(
                            array(
                                "field"   => array("user", "exam_id"),
                                "message" => "This role has already been granted"
                            )
                        ));
                } else {
                        $this->validate(new Uniqueness(
                            array(
                                "field"   => "user",
                                "message" => "This role has already been granted"
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
         * Called before persisting the model object.
         */
        protected function beforeSave()
        {
                if (!is_numeric($this->user[0])) {
                        $this->user = (new User($this->user))->getPrincipalName();
                }
        }

        private function getAttribute($name)
        {
                if (($attrs = $this->catalog->getAttribute($this->user, $name))) {
                        return current($attrs)[$name][0];
                }
        }

}
