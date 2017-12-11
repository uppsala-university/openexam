<?php

// 
// The source code is copyrighted, with equal shared rights, between the
// authors (see the file AUTHORS) and the OpenExam project, Uppsala University 
// unless otherwise explicit stated elsewhere.
// 
// File:    Resource.php
// Created: 2014-09-21 17:23:54
// 
// Author:  Anders Lövgren (Computing Department at BMC, Uppsala University)
// 

namespace OpenExam\Models;

use OpenExam\Library\Model\Behavior\Generate\Ownership;
use OpenExam\Library\Model\Behavior\Transform\Remove;
use OpenExam\Library\Model\Behavior\Transform\Trim;
use OpenExam\Library\Model\Guard\Exam as ExamModelGuard;
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
         * Guard against problematic methods use.
         */
        use ExamModelGuard;

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

                $this->belongsTo("exam_id", "OpenExam\Models\Exam", "id", array(
                        "foreignKey" => true,
                        "alias"      => 'exam',
                        'reusable'   => true
                ));

                $this->addBehavior(new Ownership(array(
                        'beforeValidationOnCreate' => array(
                                'field' => 'user',
                                'force' => false
                        ),
                        'beforeValidationOnUpdate' => array(
                                'field' => 'user',
                                'force' => false
                        )
                )));

                $this->addBehavior(new Remove(array(
                        'beforeSave' => array(
                                'field'  => 'path',
                                'search' => $this->getDI()->getConfig()->application->baseUri
                        )
                    )
                ));
                
                $this->addBehavior(new Trim(array(
                        'beforeValidationOnCreate' => array(
                                'field' => array('name', 'descr', 'path', 'type', 'subtype', 'user'),
                                'value' => null
                        ),
                        'beforeValidationOnUpdate' => array(
                                'field' => array('name', 'descr', 'path', 'type', 'subtype', 'user'),
                                'value' => null
                        )
                )));
        }

        /**
         * Get source table name.
         * @return string
         */
        public function getSource()
        {
                return 'resources';
        }

        /**
         * Get table column map.
         * @return array
         */
        public function columnMap()
        {
                return array(
                        'id'      => 'id',
                        'exam_id' => 'exam_id',
                        'name'    => 'name',
                        'descr'   => 'descr',
                        'path'    => 'path',
                        'type'    => 'type',
                        'subtype' => 'subtype',
                        'user'    => 'user',
                        'shared'  => 'shared'
                );
        }

        /**
         * Validates business rules.
         * @return boolean
         */
        protected function validation()
        {
                $this->validate(new Inclusionin(
                    array(
                        'field'  => 'shared',
                        'domain' => array(self::NOT_SHARED, self::SHARED_EXAM, self::SHARED_GLOBAL, self::SHARED_GROUP)
                    )
                ));
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

}
