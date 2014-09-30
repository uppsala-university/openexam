<?php

namespace OpenExam\Models;

use OpenExam\Library\Core\Exam\State;
use Phalcon\Mvc\Model\Behavior\Timestampable;

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
         * Is this exam a testcase?
         * @var bool
         */
        public $testcase;
        /**
         * Does this exam require client lockdown?
         * @var bool
         */
        public $lockdown;

        protected function initialize()
        {
                parent::initialize();

                $this->hasMany('id', 'OpenExam\Models\Contributor', 'exam_id', array('alias' => 'Contributors'));
                $this->hasMany('id', 'OpenExam\Models\Decoder', 'exam_id', array('alias' => 'Decoders'));
                $this->hasMany('id', 'OpenExam\Models\Invigilator', 'exam_id', array('alias' => 'Invigilators'));
                $this->hasMany('id', 'OpenExam\Models\Lock', 'exam_id', array('alias' => 'Locks'));
                $this->hasMany('id', 'OpenExam\Models\Question', 'exam_id', array('alias' => 'Questions'));
                $this->hasMany("id", "OpenExam\Models\Resource", "exam_id", array("alias" => 'Resources'));
                $this->hasMany('id', 'OpenExam\Models\Student', 'exam_id', array('alias' => 'Students'));
                $this->hasMany('id', 'OpenExam\Models\Topic', 'exam_id', array('alias' => 'Topics'));

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
        }

        /**
         * Called before model is created.
         */
        protected function beforeValidationOnCreate()
        {
                if (!isset($this->details)) {
                        $this->details = $this->getDI()->get('config')->result->details;
                }
                if (!isset($this->decoded)) {
                        $this->decoded = false;
                }
                if (!isset($this->testcase)) {
                        $this->testcase = false;
                }
                if (!isset($this->lockdown)) {
                        $this->lockdown = false;
                }
        }

        /**
         * Called before model is saved.
         */
        protected function beforeSave()
        {
                $this->decoded = $this->decoded ? 'Y' : 'N';
                $this->testcase = $this->testcase ? 'Y' : 'N';
                $this->lockdown = $this->lockdown ? 'Y' : 'N';
        }

        /**
         * Called after model is saved.
         */
        protected function afterSave()
        {
                $this->decoded = $this->decoded == 'Y';
                $this->testcase = $this->testcase == 'Y';
                $this->lockdown = $this->lockdown == 'Y';
        }

        /**
         * Called after the model was read.
         */
        protected function afterFetch()
        {
                $this->decoded = $this->decoded == 'Y';
                $this->testcase = $this->testcase == 'Y';
                $this->lockdown = $this->lockdown == 'Y';
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

}
