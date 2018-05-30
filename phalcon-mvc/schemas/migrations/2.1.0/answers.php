<?php 

use Phalcon\Db\Column;
use Phalcon\Db\Index;
use Phalcon\Db\Reference;
use Phalcon\Mvc\Model\Migration;

/**
 * Class AnswersMigration_210
 */
class AnswersMigration_210 extends Migration
{
    /**
     * Define the table structure
     *
     * @return void
     */
    public function morph()
    {
        $this->morphTable('answers', [
                'columns' => [
                    new Column(
                        'id',
                        [
                            'type' => Column::TYPE_INTEGER,
                            'notNull' => true,
                            'autoIncrement' => true,
                            'size' => 11,
                            'first' => true
                        ]
                    ),
                    new Column(
                        'question_id',
                        [
                            'type' => Column::TYPE_INTEGER,
                            'notNull' => true,
                            'size' => 11,
                            'after' => 'id'
                        ]
                    ),
                    new Column(
                        'student_id',
                        [
                            'type' => Column::TYPE_INTEGER,
                            'notNull' => true,
                            'size' => 11,
                            'after' => 'question_id'
                        ]
                    ),
                    new Column(
                        'answered',
                        [
                            'type' => Column::TYPE_CHAR,
                            'default' => "N",
                            'size' => 1,
                            'after' => 'student_id'
                        ]
                    ),
                    new Column(
                        'answer',
                        [
                            'type' => Column::TYPE_MEDIUMBLOB,
                            'size' => 1,
                            'after' => 'answered'
                        ]
                    ),
                    new Column(
                        'comment',
                        [
                            'type' => Column::TYPE_TEXT,
                            'size' => 1,
                            'after' => 'answer'
                        ]
                    )
                ],
                'indexes' => [
                    new Index('PRIMARY', ['id'], 'PRIMARY'),
                    new Index('question_id', ['question_id'], null),
                    new Index('student_id', ['student_id'], null)
                ],
                'references' => [
                    new Reference(
                        'answers_ibfk_1',
                        [
                            'referencedTable' => 'questions',
                            'referencedSchema' => 'openexam',
                            'columns' => ['question_id'],
                            'referencedColumns' => ['id'],
                            'onUpdate' => 'RESTRICT',
                            'onDelete' => 'CASCADE'
                        ]
                    ),
                    new Reference(
                        'answers_ibfk_2',
                        [
                            'referencedTable' => 'students',
                            'referencedSchema' => 'openexam',
                            'columns' => ['student_id'],
                            'referencedColumns' => ['id'],
                            'onUpdate' => 'RESTRICT',
                            'onDelete' => 'CASCADE'
                        ]
                    )
                ],
                'options' => [
                    'TABLE_TYPE' => 'BASE TABLE',
                    'AUTO_INCREMENT' => '1',
                    'ENGINE' => 'InnoDB',
                    'TABLE_COLLATION' => 'utf8_general_ci'
                ],
            ]
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
