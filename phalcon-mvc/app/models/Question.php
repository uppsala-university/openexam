<?php

namespace OpenExam\Models;

use OpenExam\Library\Security\Roles;
use Phalcon\DI as PhalconDI;
use Phalcon\DiInterface;
use Phalcon\Mvc\Model;
use Phalcon\Mvc\Model\Criteria;
use Phalcon\Mvc\Model\Query\Builder;
use Phalcon\Mvc\Model\Validator\Inclusionin;

/**
 * The question model.
 * 
 * Represent a single question. The question is by default active, but can
 * be flagged as removed. The correcting user (being in possesion of the
 * corrector role) is defined by the user property.
 * 
 * @property Answer[] $answers The answers for this question.
 * @property Corrector[] $correctors The correctors for this question.
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

        /**
         * Specialization of the query() function for the question model.
         * 
         * This function provides role based access to the question model. If 
         * the primary role is set, then the returned criteria is prepared with
         * inner joins against resp. role table.
         * 
         * The critera should ensure that only questions related to caller and
         * requested role is returned.
         * 
         * @param DiInterface $dependencyInjector
         * @return Criteria  
         */
        public static function query($dependencyInjector = null)
        {
                if (!isset($dependencyInjector)) {
                        $dependencyInjector = PhalconDI::getDefault();
                }

                $user = $dependencyInjector->get('user');
                $role = $user->getPrimaryRole();

                if ($user->getUser() == null) {
                        return parent::query($dependencyInjector);
                }
                if ($user->hasPrimaryRole() == false || Roles::isGlobal($role)) {
                        return parent::query($dependencyInjector);
                }

                $criteria = parent::query($dependencyInjector);
                if ($role == Roles::CORRECTOR) {
                        $criteria
                            ->join(self::getRelation('corrector'), self::getRelation('question', 'id', 'question_id'))
                            ->where(sprintf("user = '%s'", $user->getPrincipalName()));
                } elseif ($role == Roles::CREATOR) {
                        $criteria
                            ->join(self::getRelation('exam'), self::getRelation('exam', 'id', 'exam_id'))
                            ->where(sprintf("creator = '%s'", $user->getPrincipalName()));
                } else {
                        $criteria
                            ->join(self::getRelation('exam'), self::getRelation('question', 'exam_id', 'id', 'exam'))
                            ->join(self::getRelation($role), self::getRelation('exam', 'id', 'exam_id', $role))
                            ->andWhere(sprintf("user = '%s'", $user->getPrincipalName()));
                }

                return $criteria;
        }

        /**
         * Specialization of find() for the question model.
         * 
         * This function provides checked access for queries against the 
         * question model. If primary role is unset, user is not authenticated 
         * or if accessed using a global role (teacher, admin, trsuted or custom),
         * then the behavour is the same as calling parent::find().
         * 
         * @param array $parameters The query parameters.
         * @return mixed
         * @see http://docs.phalconphp.com/en/latest/api/Phalcon_Mvc_Model_Query_Builder.html
         * @uses Model::find()
         */
        public static function find($parameters = null)
        {
                $dependencyInjector = PhalconDI::getDefault();

                $user = $dependencyInjector->get('user');
                $role = $user->getPrimaryRole();

                // 
                // Wrap string search in array:
                // 
                if (is_string($parameters)) {
                        $parameters = array($parameters);
                }

                // 
                // Don't accept access to other models:
                // 
                if (isset($parameters['models'])) {
                        unset($parameters['models']);
                }

                // 
                // Group by question by default:
                // 
                if (!isset($parameters['group'])) {
                        $parameters['group'] = self::getRelation('question') . '.id';
                }

                // 
                // Use parent find() if user is not authenticated:
                // 
                if ($user->getUser() == null) {
                        return parent::find($parameters);
                }

                // 
                // Use parent find() if primary role is unset or if accessed 
                // using global role (these are not tied to any exam).
                // 
                if ($user->hasPrimaryRole() == false || Roles::isGlobal($role)) {
                        return parent::find($parameters);
                }

                // 
                // Create the builder using supplied options (conditions,
                // order, limit, ...):
                // 
                $builder = new Builder($parameters);

                if ($role == Roles::CORRECTOR) {
                        $builder
                            ->from(self::getRelation('question'))
                            ->join(self::getRelation('corrector'), self::getRelation('question', 'id', 'question_id'))
                            ->andWhere(sprintf("user = '%s'", $user->getPrincipalName()));
                } elseif ($role == Roles::CREATOR) {
                        $builder
                            ->from(self::getRelation('question'))
                            ->join(self::getRelation('exam'), self::getRelation('exam', 'id', 'exam_id'))
                            ->andWhere(sprintf("creator = '%s'", $user->getPrincipalName()));
                } else {
                        $builder
                            ->from(self::getRelation('question'))
                            ->join(self::getRelation('exam'), self::getRelation('question', 'exam_id', 'id', 'exam'))
                            ->join(self::getRelation($role), self::getRelation('exam', 'id', 'exam_id', $role))
                            ->andWhere(sprintf("user = '%s'", $user->getPrincipalName()));
                }

                return $builder->getQuery()->execute();
        }

}
