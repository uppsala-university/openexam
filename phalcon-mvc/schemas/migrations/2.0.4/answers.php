<?php 

use Phalcon\Db\Column;
use Phalcon\Db\Index;
use Phalcon\Db\Reference;
use Phalcon\Mvc\Model\Migration;

class AnswersMigration_204 extends Migration
{

    public function up()
    {
        $this->morphTable(
            'answers',
            array(
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
                        'size' => 1,
                        'after' => 'student_id'
                    )
                ),
                new Column(
                    'answer',
                    array(
                        'type' => Column::TYPE_TEXT,
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
                new Index('PRIMARY', array('id')),
                new Index('question_id', array('question_id')),
                new Index('student_id', array('student_id'))
            ),
            'references' => array(
                new Reference('answers_ibfk_1', array(
                    'referencedSchema' => 'openexam',
                    'referencedTable' => 'questions',
                    'columns' => array('question_id'),
                    'referencedColumns' => array('id')
                )),
                new Reference('answers_ibfk_2', array(
                    'referencedSchema' => 'openexam',
                    'referencedTable' => 'students',
                    'columns' => array('student_id'),
                    'referencedColumns' => array('id')
                ))
            ),
            'options' => array(
                'TABLE_TYPE' => 'BASE TABLE',
                'AUTO_INCREMENT' => '1030',
                'ENGINE' => 'InnoDB',
                'TABLE_COLLATION' => 'utf8_general_ci'
            )
        )
        );
    }
}
