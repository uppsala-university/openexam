<?php 

use Phalcon\Db\Column;
use Phalcon\Db\Index;
use Phalcon\Db\Reference;
use Phalcon\Mvc\Model\Migration;

/**
 * Class QuestionsMigration_2013
 */
class QuestionsMigration_2013 extends Migration
{
    /**
     * Define the table structure
     *
     * @return void
     */
    public function morph()
    {
        $this->morphTable('questions', array(
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
                        'exam_id',
                        array(
                            'type' => Column::TYPE_INTEGER,
                            'notNull' => true,
                            'size' => 11,
                            'after' => 'id'
                        )
                    ),
                    new Column(
                        'topic_id',
                        array(
                            'type' => Column::TYPE_INTEGER,
                            'notNull' => true,
                            'size' => 11,
                            'after' => 'exam_id'
                        )
                    ),
                    new Column(
                        'slot',
                        array(
                            'type' => Column::TYPE_INTEGER,
                            'default' => "0",
                            'size' => 11,
                            'after' => 'topic_id'
                        )
                    ),
                    new Column(
                        'uuid',
                        array(
                            'type' => Column::TYPE_VARCHAR,
                            'size' => 32,
                            'after' => 'slot'
                        )
                    ),
                    new Column(
                        'user',
                        array(
                            'type' => Column::TYPE_VARCHAR,
                            'notNull' => true,
                            'size' => 60,
                            'after' => 'uuid'
                        )
                    ),
                    new Column(
                        'score',
                        array(
                            'type' => Column::TYPE_FLOAT,
                            'notNull' => true,
                            'size' => 1,
                            'after' => 'user'
                        )
                    ),
                    new Column(
                        'name',
                        array(
                            'type' => Column::TYPE_VARCHAR,
                            'notNull' => true,
                            'size' => 30,
                            'after' => 'score'
                        )
                    ),
                    new Column(
                        'quest',
                        array(
                            'type' => Column::TYPE_MEDIUMBLOB,
                            'notNull' => true,
                            'size' => 1,
                            'after' => 'name'
                        )
                    ),
                    new Column(
                        'answer',
                        array(
                            'type' => Column::TYPE_BLOB,
                            'size' => 1,
                            'after' => 'quest'
                        )
                    ),
                    new Column(
                        'status',
                        array(
                            'type' => Column::TYPE_CHAR,
                            'default' => "active",
                            'notNull' => true,
                            'size' => 1,
                            'after' => 'answer'
                        )
                    ),
                    new Column(
                        'comment',
                        array(
                            'type' => Column::TYPE_TEXT,
                            'size' => 1,
                            'after' => 'status'
                        )
                    ),
                    new Column(
                        'grades',
                        array(
                            'type' => Column::TYPE_VARCHAR,
                            'size' => 200,
                            'after' => 'comment'
                        )
                    )
                ),
                'indexes' => array(
                    new Index('PRIMARY', array('id'), 'PRIMARY'),
                    new Index('exam_id', array('exam_id'), null),
                    new Index('questions_ibfk_2', array('topic_id'), null)
                ),
                'references' => array(
                    new Reference(
                        'questions_ibfk_1',
                        array(
                            'referencedSchema' => 'openexam2prod',
                            'referencedTable' => 'exams',
                            'columns' => array('exam_id','exam_id','exam_id'),
                            'referencedColumns' => array('id','id','id'),
                            'onUpdate' => 'RESTRICT',
                            'onDelete' => 'RESTRICT'
                        )
                    ),
                    new Reference(
                        'questions_ibfk_2',
                        array(
                            'referencedSchema' => 'openexam2prod',
                            'referencedTable' => 'topics',
                            'columns' => array('topic_id','topic_id','topic_id'),
                            'referencedColumns' => array('id','id','id'),
                            'onUpdate' => 'RESTRICT',
                            'onDelete' => 'RESTRICT'
                        )
                    )
                ),
                'options' => array(
                    'TABLE_TYPE' => 'BASE TABLE',
                    'AUTO_INCREMENT' => '32564',
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
