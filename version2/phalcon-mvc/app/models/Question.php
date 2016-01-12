<?php

namespace OpenExam\Models;

use OpenExam\Library\Model\Behavior\FilterText;
use OpenExam\Library\Model\Behavior\Ownership;
use OpenExam\Library\Model\Behavior\Question as QuestionBehavior;
use OpenExam\Library\Model\Behavior\UUID;
use OpenExam\Library\Security\Roles;
use Phalcon\DI as PhalconDI;
use Phalcon\Mvc\Model;
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
         * The slot (ordering) of this object.
         * @var integer 
         */
        public $slot;
        /**
         * The UUID for this object.
         * @see http://en.wikipedia.org/wiki/Universally_unique_identifier
         * @var string 
         */
        public $uuid;
        /**
         * The question publisher.
         * @var string 
         */
        public $user;
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
         * The pre-defined answer. Intended for exams where the answer is shown
         * to students in response to saving the answer (bläddertentor).
         * @var string 
         */
        public $answer;
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

                $this->hasMany('id', 'OpenExam\Models\Answer', 'question_id', array(
                        'alias' => 'answers'
                ));
                $this->hasMany('id', 'OpenExam\Models\Corrector', 'question_id', array(
                        'alias' => 'correctors'
                ));
                $this->belongsTo('exam_id', 'OpenExam\Models\Exam', 'id', array(
                        'foreignKey' => true,
                        'alias'      => 'exam'
                ));
                $this->belongsTo('topic_id', 'OpenExam\Models\Topic', 'id', array(
                        'foreignKey' => true,
                        'alias'      => 'topic'
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
                $this->addBehavior(new UUID(array(
                        'beforeCreate' => array(
                                'field' => 'uuid',
                                'force' => true
                        ),
                        'beforeUpdate' => array(
                                'field' => 'uuid',
                                'force' => false
                        )
                )));

                // 
                // TODO: better do filtering on client side.
                // 
                $this->addBehavior(new FilterText(array(
                        'beforeValidationOnCreate' => array(
                                'fields' => array('quest', 'answer', 'comment')
                        ),
                        'beforeValidationOnUpdate' => array(
                                'fields' => array('quest', 'answer', 'comment')
                        )
                    )
                ));

                $this->addBehavior(new QuestionBehavior());
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
                if (!isset($this->slot)) {
                        $this->slot = self::count(sprintf("exam_id = %d", $this->exam_id)) + 1;
                }
        }

        public function getSource()
        {
                return 'questions';
        }

        /**
         * Specialization of findFirst() for the question model.
         * 
         * @param array $parameters The query parameters.
         * @return Model
         * @uses Question::find()
         * 
         * @see http://docs.phalconphp.com/en/latest/reference/models.html#finding-records
         * @see http://docs.phalconphp.com/en/latest/api/Phalcon_Mvc_Model_Query_Builder.html
         */
        public static function findFirst($parameters = null)
        {
                if (!isset($parameters)) {
                        $parameters = array('limit' => 1);
                }
                if (is_numeric($parameters)) {
                        $parameters = "id = $parameters";
                }
                if (is_string($parameters)) {
                        $parameters = array('conditions' => $parameters, 'limit' => 1);
                }
                if (!isset($parameters['limit'])) {
                        $parameters['limit'] = 1;
                }
                return self::find($parameters)->getFirst();
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
         * @uses Model::find()
         * 
         * @see http://docs.phalconphp.com/en/latest/reference/models.html#finding-records
         * @see http://docs.phalconphp.com/en/latest/api/Phalcon_Mvc_Model_Query_Builder.html
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
                // Qualify bind and order parameters:
                // 
                $parameters = self::getParameters(self::getRelation('question'), $parameters);

                // 
                // Create the builder using supplied options (conditions,
                // order, limit, ...):
                // 
                $builder = new Builder($parameters);

                if ($role == Roles::CORRECTOR) {
                        $builder
                            ->from(self::getRelation('question'))
                            ->join(self::getRelation('corrector'), self::getRelation('question', 'id', 'question_id', 'corrector'))
                            ->andWhere(sprintf("%s.user = '%s'", self::getRelation('corrector'), $user->getPrincipalName()));
                } elseif ($role == Roles::CREATOR) {
                        $builder
                            ->from(self::getRelation('question'))
                            ->join(self::getRelation('exam'), self::getRelation('question', 'exam_id', 'id', 'exam'))
                            ->andWhere(sprintf("%s.creator = '%s'", self::getRelation('exam'), $user->getPrincipalName()));
                } else {
                        $builder
                            ->from(self::getRelation('question'))
                            ->join(self::getRelation('exam'), self::getRelation('question', 'exam_id', 'id', 'exam'))
                            ->join(self::getRelation($role), self::getRelation($role, 'exam_id', 'id', 'exam'))
                            ->andWhere(sprintf("%s.user = '%s'", self::getRelation($role), $user->getPrincipalName()));
                }

                if (isset($parameters['bind'])) {
                        return $builder->getQuery()->execute($parameters['bind']);
                } else {
                        return $builder->getQuery()->execute();
                }
        }

        /**
         * Returns PHQL prepared with joins against role models. 
         * 
         * When used in a query, it will ensure that only exams where caller 
         * has the primary role is returned. Notice that the joins includes
         * a WHERE clause.
         * 
         * <code>
         * $result = $this->modelsManager->executeQuery(
         *      "SELECT Question.* FROM " . Question::getRelations() . " AND Question.name LIKE '%test%'"
         * );
         * </code>
         * 
         * @return string
         * @see getQuery
         */
        private static function getRelations()
        {
                $dependencyInjector = PhalconDI::getDefault();

                $user = $dependencyInjector->get('user');
                $role = $user->getPrimaryRole();

                $builder = new Builder();

                if ($user->hasPrimaryRole() == false || Roles::isGlobal($role)) {
                        $builder
                            ->addFrom(self::getRelation('question'), 'Question');
                } elseif ($role == Roles::CORRECTOR) {
                        $builder
                            ->addFrom(self::getRelation('question'), 'Question')
                            ->join(self::getRelation('corrector'), 'Question.id = Corrector.question_id', 'Corrector')
                            ->andWhere(sprintf("Corrector.user = '%s'", $user->getPrincipalName()));
                } elseif ($role == Roles::CREATOR) {
                        $builder
                            ->addFrom(self::getRelation('question'), 'Question')
                            ->join(self::getRelation('exam'), 'Exam.id = Question.exam_id', 'Exam')
                            ->andWhere(sprintf("Exam.creator = '%s'", $user->getPrincipalName()));
                } else {
                        $builder
                            ->addFrom(self::getRelation('question'), 'Question')
                            ->join(self::getRelation('exam'), 'Exam.id = Question.exam_id', 'Exam')
                            ->join(self::getRelation($role), sprintf("%s.exam_id = Exam.id", ucfirst($role)), ucfirst($role))
                            ->andWhere(sprintf("%s.user = '%s'", ucfirst($role), $user->getPrincipalName()));
                }

                $query = $builder->getPhql();
                return substr($query, strpos($query, "FROM") + 5);
        }

        /**
         * Get joined PHQL query.
         * 
         * <code>
         * // 
         * // Simple queries:
         * // 
         * $query = "SELECT Question.* FROM Question";
         * $query = "SELECT Question.* FROM Question LIMIT 1";
         * $query = "SELECT Question.* FROM Question LIMIT 3, OFFSET 6";
         * $query = "SELECT Question.* FROM Question WHERE Question.name LIKE '%test%'";
         * $query = "SELECT Question.* FROM Question WHERE Question.id IN (3,5,14)";
         * $query = "SELECT Question.* FROM Question WHERE Question.id = 10 AND Question.name = 'Name'";
         * $query = "SELECT Question.* FROM Question WHERE Exam.id = 123";
         * 
         * $result = $this->modelsManager->executeQuery(
         *      Question::getQuery($query)
         * );
         * </code>
         * 
         * <code>
         * // 
         * // Using bind parameters:
         * // 
         * $query = "SELECT Question.* FROM Question WHERE Question.id = ?0 AND Question.name = ?1";
         * $result = $this->modelsManager->executeQuery(
         *      Question::getQuery($query), array(10, 'Name')
         * );
         * 
         * $query = "SELECT Question.* FROM Question WHERE Question.id = :id: AND Question.name = :name:";
         * $result = $this->modelsManager->executeQuery(
         *      Question::getQuery($query), array('id' => 10, 'name' => 'Name')
         * );
         * </code>
         * 
         * Implicit joined models can be part of the where clause. This example
         * shows this when primary role is set to student:
         * <code>
         * $query = "SELECT Question.* FROM Question WHERE Student.tag LIKE '3FM%'";
         * 
         * $result = $this->modelsManager->executeQuery(
         *      Question::getQuery($query)
         * );
         * </code>
         * 
         * @param string $query The query string.
         * @return string
         */
        public static function getQuery($query)
        {
                $relations = self::getRelations();

                list($qs, $qe) = explode(" Question ", $query . ' ');

                $qs = trim($qs);
                $qe = trim($qe);

                if (strlen($qe) == 0) {
                        $result = sprintf("%s %s", $qs, $relations);
                } elseif (strpos($qe, "WHERE") !== false && strpos($relations, "WHERE") !== false) {
                        $result = sprintf("%s %s AND %s", $qs, $relations, substr($qe, 6));
                } else {
                        $result = sprintf("%s %s %s", $qs, $relations, $qe);
                }

                return $result;
        }

}
