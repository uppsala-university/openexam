<?php

namespace OpenExam\Models;

use OpenExam\Library\Security\User;
use Phalcon\Mvc\Model\Validator\Inclusionin;

/**
 * The resource model.
 * 
 * Represents a resource file used in a question on an exam. Resources are
 * for example multimedia files, but also equation collections. 
 * 
 * This model deals only with resources stored local. Remote resources are
 * represented as links withing the questions itself.
 * 
 * An resource is by default shared withing the exam in which they were
 * uploaded. The sharing can be overridden by setting the shared property
 * to either 'private', 'group' or 'global'. For group sharing, the access
 * is dynamic defined by users primary group (e.g. from LDAP).
 * 
 * @property Exam $exam The related exam.
 * @author Anders Lövgren (QNET/BMC CompDept)
 */
class Resource extends ModelBase
{

        /**
         * Resource is not shared.
         */
        const NOT_SHARED = 'private';
        /**
         * Resource is shared with other contributors in this exam. 
         */
        const SHARED_EXAM = 'exam';
        /**
         * Resource is shared with other people in the same group.
         */
        const SHARED_GROUP = 'group';
        /**
         * Resource is shared globally.
         */
        const SHARED_GLOBAL = 'global';

        /**
         * The object ID.
         * @var integer
         */
        public $id;
        /**
         * The exam ID.
         * @var integer
         */
        public $exam_id;
        /**
         * The file name (descriptive).
         * @var string
         */
        public $name;
        /**
         * The file path.
         * @var string
         */
        public $path;
        /**
         * The MIME type (e.g. video).
         * @var string
         */
        public $type;
        /**
         * The MIME subtype (e.g. pdf).
         * @var string
         */
        public $subtype;
        /**
         * The user principal name (e.g. user@example.com).
         * @var string
         */
        public $user;
        /**
         * The sharing level.
         * @var string
         */
        public $shared;

        protected function initialize()
        {
                parent::initialize();
                $this->belongsTo("exam_id", "OpenExam\Models\Exam", "id", array("foreignKey" => true, "alias" => 'exam'));
        }

        /**
         * Validates business rules.
         * @return boolean
         */
        protected function validation()
        {
                $this->validate(new Inclusionin(array(
                        'field'  => 'shared',
                        'domain' => array(self::NOT_SHARED, self::SHARED_EXAM, self::SHARED_GLOBAL, self::SHARED_GROUP)
                )));
                if ($this->validationHasFailed() == true) {
                        return false;
                }
        }

        /**
         * Called before model is created.
         */
        protected function beforeValidationOnCreate()
        {
                if (!isset($this->shared)) {
                        $this->shared = self::SHARED_EXAM;
                }
        }

        /**
         * Called before persisting the model object.
         */
        protected function beforeSave()
        {
                $this->user = (new User($this->user))->getPrincipalName();
        }

        public function getSource()
        {
                return 'resources';
        }

}
