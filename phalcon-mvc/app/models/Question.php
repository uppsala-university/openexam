<?php

namespace OpenExam\Models;

use Phalcon\Mvc\Model\Validator\Inclusionin;

/**
 * The question model.
 * 
 * Represent a single question. The question is by default active, but can
 * be flagged as removed. The correcting user (being in possesion of the
 * corrector role) is defined by the user property.
 * 
 * @property Answer $answers The answers for this question.
 * @property Exam $exam The related exam.
 * @property Topic $topic The related topic.
 * @author Anders Lövgren (QNET/BMC CompDept)
 */
class Question extends ModelBase
{

        /**
         * This question is active.
         */
        const STATUS_ACTIVE = 'active';
        /**
         * This question is removed.
         */
        const STATUS_REMOVED = 'removed';

        /**
         * This object ID.
         * @var integer
         */
        public $id;
        /**
         * The exam ID.
         * @var integer
         */
        public $exam_id;
        /**
         * The topic ID.
         * @var integer
         */
        public $topic_id;
        /**
         * The question score.
         * @var double
         */
        public $score;
        /**
         * The question name (title).
         * @var string
         */
        public $name;
        /**
         * The question text.
         * @var string
         */
        public $quest;
        /**
         * The question correctors.
         * @var array
         */
        public $user;
        /**
         * The question status (see STATUS_XXX).
         * @var string
         */
        public $status = 'active';
        /**
         * Comment for this question.
         * @var string
         */
        public $comment;
        /**
         * Source code for answer score evaluation (for this question).
         * @var string
         */
        public $grades;

        /**
         * Initialize method for model.
         */
        protected function initialize()
        {
                parent::initialize();
                $this->hasMany('id', 'OpenExam\Models\Answer', 'question_id', array('alias' => 'Answers'));
                $this->belongsTo('exam_id', 'OpenExam\Models\Exam', 'id', array('foreignKey' => true, 'alias' => 'Exam'));
                $this->belongsTo('topic_id', 'OpenExam\Models\Topic', 'id', array('foreignKey' => true, 'alias' => 'Topic'));
        }

        /**
         * Validates business rules.
         * @return boolean
         */
        protected function validation()
        {
                $this->validate(new Inclusionin(array(
                        'field'  => 'status',
                        'domain' => array('active', 'removed')
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
                $this->status = 'active';
        }

        /**
         * Called before model is saved.
         */
        protected function beforeSave()
        {
                $this->user = json_encode($this->user);
        }

        /**
         * Called after model is saved.
         */
        protected function afterSave()
        {
                $this->user = json_decode($this->user);
        }

        /**
         * Called after the model was read.
         */
        protected function afterFetch()
        {
                $this->user = json_decode($this->user);
        }

        public function getSource()
        {
                return 'questions';
        }

}
