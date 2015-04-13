<?php

namespace OpenExam\Models;

use OpenExam\Library\Core\Exam\Grades;
use OpenExam\Library\Core\Exam\State;
use OpenExam\Library\Model\Behavior\Exam as ExamBehavior;
use OpenExam\Library\Model\Behavior\Ownership;
use OpenExam\Library\Model\Filter;
use OpenExam\Library\Security\Roles;
use Phalcon\DI as PhalconDI;
use Phalcon\Mvc\Model;
use Phalcon\Mvc\Model\Behavior\Timestampable;
use Phalcon\Mvc\Model\Query\Builder;

/**
 * The exam model.
 * 
 * Represents an exam. This class is the central model to which most other
 * models are related.
 * 
 * An exam is in one of these states: preparing, upcoming, active, finished 
 * or decoded.
 * 
 * The grades property (array) is defined as JSON in the database. It is
 * either an object defining grades or an array defining the function body
 * for a function that evaluates the final score for a single student on 
 * the exam:
 * 
 * <code>
 * { data: { "U":"0", "G":"55", "VG":"80" } }
 * { func: { // source code } }
 * </code>
 * 
 * @property Contributor[] $contributors The contributors for this exam.
 * @property Decoder[] $decoders The decoders for this exam.
 * @property Invigilator[] $invigilators The invigilators for this exam.
 * @property Lock[] $locks The computer locks aquired for this exam.
 * @property Resource[] $resources The multimedia or resource files associated with this exam.
 * @property Question[] $questions The questions that belongs to this exam.
 * @property Student[] $students The students assigned to this exam.
 * @property Topic[] $topics The topics associated with this exam.
 * @property Access[] $access The access definitions associated with this exam.
 * 
 * @author Anders Lövgren (QNET/BMC CompDept)
 */
class Exam extends ModelBase
{

        /**
         * Show responsible people for examination.
         */
        const RESULT_EXPOSE_EMPLOYEES = 1;
        /**
         * Include statistics of all students.
         */
        const RESULT_OTHERS_STATISTICS = 2;
        /**
         * Expose student grade and percentage during correction phase.
         */
        const EXPOSE_GRADES_DURING_CORRECTION = 4;

        /**
         * This object ID.
         * @var integer
         */
        public $id;
        /**
         * The name of the exam.
         * @var string
         */
        public $name;
        /**
         * The exam description.
         * @var string
         */
        public $descr;
        /**
         * The exam start date/time (might be null).
         * @var string
         */
        public $starttime;
        /**
         * The exam end date/time (might be null).
         * @var string
         */
        public $endtime;
        /**
         * The exam create date/time.
         * @var string
         */
        public $created;
        /**
         * The exam update date/time.
         * @var string
         */
        public $updated;
        /**
         * The creator of the exam.
         * @var string
         */
        public $creator;
        /**
         * Bitmask of exposed details in result (see RESULT_XXX constants).
         * @var integer
         */
        public $details;
        /**
         * Is this exam decoded?
         * @var bool
         */
        public $decoded;
        /**
         * Is this exam published?
         * @var bool
         */
        public $published;
        /**
         * The organization unit.
         * @var string
         */
        public $orgunit;
        /**
         * The exam grades.
         * @var string
         */
        public $grades;
        /**
         * The course code related to this exam.
         * @var string 
         */
        public $course;
        /**
         * The unique exam code.
         * @var string 
         */
        public $code;
        /**
         * Is this exam a testcase?
         * @var bool
         */
        public $testcase;
        /**
         * Does this exam require client lockdown?
         * @var object
         */
        public $lockdown;
        /**
         * Examination state (bitmask).
         * @var int 
         */
        public $state;
        /**
         * Examination flags (e.g. decodable).
         * @var string[]
         */
        public $flags;

        protected function initialize()
        {
                parent::initialize();

                $this->hasMany('id', 'OpenExam\Models\Contributor', 'exam_id', array(
                        'alias' => 'contributors'
                ));
                $this->hasMany('id', 'OpenExam\Models\Decoder', 'exam_id', array(
                        'alias' => 'decoders'
                ));
                $this->hasMany('id', 'OpenExam\Models\Invigilator', 'exam_id', array(
                        'alias' => 'invigilators'
                ));
                $this->hasMany('id', 'OpenExam\Models\Lock', 'exam_id', array(
                        'alias' => 'locks'
                ));
                $this->hasMany('id', 'OpenExam\Models\Question', 'exam_id', array(
                        'alias' => 'questions'
                ));
                $this->hasMany("id", "OpenExam\Models\Resource", "exam_id", array(
                        'alias' => 'resources'
                ));
                $this->hasMany('id', 'OpenExam\Models\Student', 'exam_id', array(
                        'alias' => 'students'
                ));
                $this->hasMany('id', 'OpenExam\Models\Topic', 'exam_id', array(
                        'alias' => 'topics'
                ));
                $this->hasMany('id', 'OpenExam\Models\Access', 'exam_id', array(
                        'alias' => 'access'
                ));

                $this->addBehavior(new Timestampable(array(
                        'beforeValidationOnCreate' => array(
                                'field'  => 'updated',
                                'format' => 'Y-m-d H:i:s'
                        ),
                        'beforeValidationOnUpdate' => array(
                                'field'  => 'updated',
                                'format' => 'Y-m-d H:i:s'
                        )
                )));

                $this->addBehavior(new Timestampable(array(
                        'beforeValidationOnCreate' => array(
                                'field'  => 'created',
                                'format' => 'Y-m-d H:i:s'
                        )
                )));

                $this->addBehavior(new Ownership(array(
                        'beforeValidationOnCreate' => array(
                                'field' => 'creator',
                                'force' => false
                        ),
                        'beforeValidationOnUpdate' => array(
                                'field' => 'creator',
                                'force' => false
                        )
                )));

                $this->addBehavior(new ExamBehavior());
        }

        /**
         * Called before model is created.
         */
        protected function beforeValidationOnCreate()
        {
                if (!isset($this->details)) {
                        $this->details = $this->getDI()->getConfig()->result->details;
                }
                if (!isset($this->decoded)) {
                        $this->decoded = false;
                }
                if (!isset($this->published)) {
                        $this->published = false;
                }
                if (!isset($this->testcase)) {
                        $this->testcase = false;
                }
                if (!isset($this->lockdown)) {
                        $this->lockdown = (object) array('enable' => false);
                }
        }

        /**
         * Called before model is saved.
         */
        protected function beforeSave()
        {
                $this->decoded = $this->decoded ? 'Y' : 'N';
                $this->published = $this->published ? 'Y' : 'N';
                $this->testcase = $this->testcase ? 'Y' : 'N';
                if (!is_string($this->lockdown)) {
                        $this->lockdown = json_encode($this->lockdown);
                }
        }

        /**
         * Called after model is saved.
         */
        protected function afterSave()
        {
                $this->decoded = $this->decoded == 'Y';
                $this->published = $this->published == 'Y';
                $this->testcase = $this->testcase == 'Y';
                $this->lockdown = json_decode($this->lockdown);
        }

        /**
         * Called after the model was read.
         */
        protected function afterFetch()
        {
                $this->decoded = $this->decoded == 'Y';
                $this->published = $this->published == 'Y';
                $this->testcase = $this->testcase == 'Y';
                $this->lockdown = json_decode($this->lockdown);
                $state = new State($this);
                $this->state = $state->getState();
                $this->flags = $state->getFlags();
                $this->setStudentDatetime();

                parent::afterFetch();
        }

        public function getSource()
        {
                return 'exams';
        }

        /**
         * Get examination state.
         * @return State
         */
        public function getState()
        {
                return new State($this);
        }

        /**
         * Get examination graduation.
         * @return Grades
         */
        public function getGrades()
        {
                return new Grades($this);
        }

        /**
         * Get filter for result set.
         * @param array $params The query parameters.
         * @return Filter The result set filter object.
         */
        public function getFilter($params)
        {
                $filter = array();

                foreach (array('state', 'flags') as $key) {
                        if (isset($params[$key])) {
                                $filter[$key] = $params[$key];
                        }
                }

                if (count($filter) != 0) {
                        return new Filter($filter);
                } else {
                        return parent::getFilter($params);
                }
        }

        /**
         * Specialization of findFirst() for the exam model.
         * 
         * @param array $parameters The query parameters.
         * @return Model
         * @uses Exam::find()
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
         * Specialization of find() for the exam model.
         * 
         * This function provides checked access for queries against the exam
         * model. If primary role is unset, user is not authenticated or if
         * accessed using a global role (teacher, admin, trusted or custom),
         * then the behavior is the same as calling parent::find().
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
                // Group by exam by default:
                // 
                if (!isset($parameters['group'])) {
                        $parameters['group'] = self::getRelation('exam') . '.id';
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
                $parameters = self::getParameters(self::getRelation('exam'), $parameters);

                // 
                // Create the builder using supplied options (conditions,
                // order, limit, ...):
                // 
                $builder = new Builder($parameters);
                $builder->from(self::getRelation('exam'));

                if ($role == Roles::CORRECTOR) {
                        $builder
                            ->from(self::getRelation('exam'))
                            ->join(self::getRelation('question'), self::getRelation('exam', 'id', 'exam_id', 'question'))
                            ->join(self::getRelation('corrector'), self::getRelation('question', 'id', 'question_id', 'corrector'))
                            ->andWhere(sprintf("%s.user = '%s'", self::getRelation('corrector'), $user->getPrincipalName()));
                } elseif ($role == Roles::CREATOR) {
                        $builder
                            ->from(self::getRelation('exam'))
                            ->andWhere(sprintf("%s.creator = '%s'", self::getRelation('exam'), $user->getPrincipalName()));
                } else {
                        $builder
                            ->from(self::getRelation('exam'))
                            ->join(self::getRelation($role), self::getRelation('exam', 'id', 'exam_id'))
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
         *      "SELECT Exam.* FROM " . Exam::getRelations() . " AND Exam.name LIKE '%test%'"
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
                            ->addFrom(self::getRelation('exam'), 'Exam');
                } elseif ($role == Roles::CORRECTOR) {
                        $builder
                            ->addFrom(self::getRelation('exam'), 'Exam')
                            ->join(self::getRelation('question'), 'Exam.id = Question.exam_id', 'Question')
                            ->join(self::getRelation('corrector'), 'Question.id = Corrector.question_id', 'Corrector')
                            ->andWhere(sprintf("Corrector.user = '%s'", $user->getPrincipalName()));
                } elseif ($role == Roles::CREATOR) {
                        $builder
                            ->addFrom(self::getRelation('exam'), 'Exam')
                            ->andWhere(sprintf("Exam.creator = '%s'", $user->getPrincipalName()));
                } else {
                        $builder
                            ->addFrom(self::getRelation('exam'), 'Exam')
                            ->join(self::getRelation($role), "$role.exam_id = Exam.id", $role)
                            ->andWhere(sprintf("%s.user = '%s'", $role, $user->getPrincipalName()));
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
         * $query = "SELECT Exam.* FROM Exam";
         * $query = "SELECT Exam.* FROM Exam LIMIT 1";
         * $query = "SELECT Exam.* FROM Exam LIMIT 3, OFFSET 6";
         * $query = "SELECT Exam.* FROM Exam WHERE Exam.name LIKE '%test%'";
         * $query = "SELECT Exam.* FROM Exam WHERE Exam.id IN (3,5,14)";
         * $query = "SELECT Exam.* FROM Exam WHERE Exam.id = 10 AND Exam.name = 'Name'";
         * 
         * $result = $this->modelsManager->executeQuery(
         *      Exam::getQuery($query)
         * );
         * </code>
         * 
         * <code>
         * // 
         * // Using bind parameters:
         * // 
         * $query = "SELECT Exam.* FROM Exam WHERE Exam.id = ?0 AND Exam.name = ?1";
         * $result = $this->modelsManager->executeQuery(
         *      Exam::getQuery($query), array(10, 'Name')
         * );
         * 
         * $query = "SELECT Exam.* FROM Exam WHERE Exam.id = :id: AND Exam.name = :name:";
         * $result = $this->modelsManager->executeQuery(
         *      Exam::getQuery($query), array('id' => 10, 'name' => 'Name')
         * );
         * </code>
         * 
         * Implicit joined models can be part of the where clause. This example
         * shows this when primary role is set to student:
         * <code>
         * $query = "SELECT Exam.* FROM Exam WHERE Student.user LIKE '%@example.com'";
         * 
         * $result = $this->modelsManager->executeQuery(
         *      Exam::getQuery($query)
         * );
         * </code>
         * 
         * @param string $query The query string.
         * @return string
         */
        public static function getQuery($query)
        {
                $relations = self::getRelations();

                list($qs, $qe) = explode(" Exam ", $query . ' ');

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

        /**
         * Modify start and/or end time.
         * 
         * Dynamic patch the starttime and/or endtime "on the fly" if called
         * having the student role. The datetime is taken from the student
         * table allowing to have per student (individual) start/end different
         * than the exam default.
         * 
         * @throws \OpenExam\Library\Model\Exception
         */
        private function setStudentDatetime()
        {
                // 
                // Adjust start/endtime per student:
                // 
                $user = $this->getDI()->get('user');
                $role = $user->setPrimaryRole(Roles::SYSTEM);

                if ($role == Roles::STUDENT) {
                        if (($student = Student::findFirst(array(
                                    'conditions' => 'user = :user: AND exam_id = :exam:',
                                    'bind'       => array(
                                            'user' => $user->getprincipalName(),
                                            'exam' => $this->id
                                )))) == false) {
                                $user->setPrimaryRole($role);
                                throw new \OpenExam\Library\Model\Exception("Failed lookup student by behavior");
                        }

                        if (isset($student->starttime)) {
                                $this->starttime = $student->starttime;
                        }
                        if (isset($student->endtime)) {
                                $this->endtime = $student->endtime;
                        }
                }
                $user->setPrimaryRole($role);
        }

}
