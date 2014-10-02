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
 * @property Corrector $correctors The correctors for this question.
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

        protected function initialize()
        {
                parent::initialize();
                $this->hasMany('id', 'OpenExam\Models\Answer', 'question_id', array('alias' => 'answers'));
                $this->hasMany('id', 'OpenExam\Models\Corrector', 'question_id', array('alias' => 'correctors'));
                $this->belongsTo('exam_id', 'OpenExam\Models\Exam', 'id', array('foreignKey' => true, 'alias' => 'exam'));
                $this->belongsTo('topic_id', 'OpenExam\Models\Topic', 'id', array('foreignKey' => true, 'alias' => 'topic'));
        }

        /**
         * Validates business rules.
         * @return boolean
         */
        protected function validation()
        {
                $this->validate(new Inclusionin(array(
                        'field'  => 'status',
                        'domain' => array(self::STATUS_ACTIVE, self::STATUS_REMOVED)
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
                if (!isset($this->status)) {
                        $this->status = self::STATUS_ACTIVE;
                }
        }

        public function getSource()
        {
                return 'questions';
        }

}
