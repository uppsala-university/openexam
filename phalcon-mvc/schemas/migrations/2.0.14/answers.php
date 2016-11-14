<?php 

use Phalcon\Db\Column;
use Phalcon\Db\Index;
use Phalcon\Db\Reference;
use Phalcon\Mvc\Model\Migration;

/**
 * Class AnswersMigration_2014
 */
class AnswersMigration_2014 extends Migration
{
    /**
     * Define the table structure
     *
     * @return void
     */
    public function morph()
    {
        $this->morphTable('answers', array(
                'columns' => array(
                    new Column(
                        'id',
                        array(
                            'type' => Column::TYPE_INTEGER,
                            'notNull' => true,
                            'autoIncrement' => true,
                            'size' => 11,
                            'first' => true
                        )
                    ),
                    new Column(
                        'question_id',
                        array(
                            'type' => Column::TYPE_INTEGER,
                            'notNull' => true,
                            'size' => 11,
                            'after' => 'id'
                        )
                    ),
                    new Column(
                        'student_id',
                        array(
                            'type' => Column::TYPE_INTEGER,
                            'notNull' => true,
                            'size' => 11,
                            'after' => 'question_id'
                        )
                    ),
                    new Column(
                        'answered',
                        array(
                            'type' => Column::TYPE_CHAR,
                            'default' => "N",
                            'size' => 1,
                            'after' => 'student_id'
                        )
                    ),
                    new Column(
                        'answer',
                        array(
                            'type' => Column::TYPE_MEDIUMBLOB,
                            'size' => 1,
                            'after' => 'answered'
                        )
                    ),
                    new Column(
                        'comment',
                        array(
                            'type' => Column::TYPE_TEXT,
                            'size' => 1,
                            'after' => 'answer'
                        )
                    )
                ),
                'indexes' => array(
                    new Index('PRIMARY', array('id'), 'PRIMARY'),
                    new Index('question_id', array('question_id'), null),
                    new Index('student_id', array('student_id'), null)
                ),
                'references' => array(
                    new Reference(
                        'answers_ibfk_1',
                        array(
                            'referencedSchema' => 'openexam2prod',
                            'referencedTable' => 'questions',
                            'columns' => array('question_id','question_id','question_id'),
                            'referencedColumns' => array('id','id','id'),
                            'onUpdate' => 'RESTRICT',
                            'onDelete' => 'RESTRICT'
                        )
                    ),
                    new Reference(
                        'answers_ibfk_2',
                        array(
                            'referencedSchema' => 'openexam2prod',
                            'referencedTable' => 'students',
                            'columns' => array('student_id','student_id','student_id'),
                            'referencedColumns' => array('id','id','id'),
                            'onUpdate' => 'RESTRICT',
                            'onDelete' => 'RESTRICT'
                        )
                    )
                ),
                'options' => array(
                    'TABLE_TYPE' => 'BASE TABLE',
                    'AUTO_INCREMENT' => '135861',
                    'ENGINE' => 'InnoDB',
                    'TABLE_COLLATION' => 'utf8_general_ci'
                ),
            )
        );
    }

    /**
     * Run the migrations
     *
     * @return void
     */
    public function up()
    {

    }

    /**
     * Reverse the migrations
     *
     * @return void
     */
    public function down()
    {

    }

}
