<?php

// 
// The source code is copyrighted, with equal shared rights, between the
// authors (see the file AUTHORS) and the OpenExam project, Uppsala University 
// unless otherwise explicit stated elsewhere.
// 
// File:    File.php
// Created: 2014-09-21 17:23:54
// 
// Author:  Anders Lövgren (Computing Department at BMC, Uppsala University)
// 

namespace OpenExam\Models;

use OpenExam\Library\Model\Behavior\Transform\Remove;
use OpenExam\Library\Model\Behavior\Transform\Trim;
use OpenExam\Library\Model\Guard\Answer as AnswerModelGuard;
use Phalcon\Mvc\Model\Validator\Uniqueness;

/**
 * The file model.
 * 
 * Represent an file uploaded as part of an question answer.
 * 
 * @property Answer $answer The related answer.
 * @author Anders Lövgren (QNET/BMC CompDept)
 */
class File extends ModelBase
{

        /**
         * Guard against problematic methods use.
         */
        use AnswerModelGuard;

        /**
         * The object ID.
         * @var integer
         */
        public $id;
        /**
         * The answer ID.
         * @var integer
         */
        public $answer_id;
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

        protected function initialize()
        {
                parent::initialize();

                $this->belongsTo("answer_id", "OpenExam\Models\Answer", "id", array(
                        "foreignKey" => true,
                        "alias"      => 'answer'
                ));

                $this->addBehavior(new Remove(array(
                        'beforeSave' => array(
                                'field'  => 'path',
                                'search' => $this->getDI()->getConfig()->application->baseUri
                        )
                    )
                ));
                
                $this->addBehavior(new Trim(array(
                        'beforeValidationOnCreate' => array(
                                'field' => array('name', 'path', 'type', 'subtype'),
                                'value' => null
                        ),
                        'beforeValidationOnUpdate' => array(
                                'field' => array('name', 'path', 'type', 'subtype'),
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
                return 'files';
        }

        /**
         * Get table column map.
         * @return array
         */
        public function columnMap()
        {
                return array(
                        'id'        => 'id',
                        'answer_id' => 'answer_id',
                        'name'      => 'name',
                        'path'      => 'path',
                        'type'      => 'type',
                        'subtype'   => 'subtype'
                );
        }

        /**
         * Validates business rules.
         * @return boolean
         */
        protected function validation()
        {
                $this->validate(new Uniqueness(
                    array(
                        'field'   => array('answer_id', 'name'),
                        'message' => "This answer already has an file named $this->name"
                    )
                ));
                $this->validate(new Uniqueness(
                    array(
                        'field'   => array('answer_id', 'path'),
                        'message' => "This answer already has an file at this location"
                    )
                ));

                if ($this->validationHasFailed() == true) {
                        return false;
                }
        }

}
