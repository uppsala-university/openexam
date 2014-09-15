<?php

namespace OpenExam\Models;

/**
 * The question model.
 * 
 * Represent a single question. The question is by default active, but can
 * be flagged as removed. The correcting user (being in possesion of the
 * corrector role) is defined by the user property (exposed for convenience
 * as an array by the users property).
 * 
 * @property Answer $Answers The answers for this question.
 * @property Exam $Exam The related exam.
 * @property Topic $Topic The related topic.
 * @property array $users The array of question correctors.
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
         * The user string.
         * @var array
         */
        protected $user;
        /**
         * The question status (see STATUS_XXX).
         * @var string
         */
        public $status;
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
        public function initialize()
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
        public function validation()
        {
                $this->validate(new InclusionInValidator(array(
                        'field'  => 'status',
                        'domain' => array('active', 'removed')
                )));
                if ($this->validationHasFailed() == true) {
                        return false;
                }
        }

        /**
         * Called before the model is created.
         */
        public function beforeCreate()
        {
                $this->status = 'active';
        }

        /**
         * Called before the model is saved.
         */
        public function beforeSave()
        {
                $this->user = json_encode($this->users);
        }

        /**
         * Called after the model was read.
         */
        public function afterFetch()
        {
                $this->users = json_decode($this->user);
        }

        public function getSource()
        {
                return 'questions';
        }

}
