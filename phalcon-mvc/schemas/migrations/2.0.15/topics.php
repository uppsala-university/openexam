<?php 

use Phalcon\Db\Column;
use Phalcon\Db\Index;
use Phalcon\Db\Reference;
use Phalcon\Mvc\Model\Migration;

/**
 * Class TopicsMigration_2015
 */
class TopicsMigration_2015 extends Migration
{
    /**
     * Define the table structure
     *
     * @return void
     */
    public function morph()
    {
        $this->morphTable('topics', array(
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
                        'slot',
                        array(
                            'type' => Column::TYPE_INTEGER,
                            'default' => "0",
                            'size' => 11,
                            'after' => 'exam_id'
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
                        'name',
                        array(
                            'type' => Column::TYPE_VARCHAR,
                            'notNull' => true,
                            'size' => 50,
                            'after' => 'uuid'
                        )
                    ),
                    new Column(
                        'randomize',
                        array(
                            'type' => Column::TYPE_INTEGER,
                            'default' => "0",
                            'notNull' => true,
                            'size' => 11,
                            'after' => 'name'
                        )
                    ),
                    new Column(
                        'grades',
                        array(
                            'type' => Column::TYPE_VARCHAR,
                            'size' => 500,
                            'after' => 'randomize'
                        )
                    ),
                    new Column(
                        'depend',
                        array(
                            'type' => Column::TYPE_VARCHAR,
                            'size' => 500,
                            'after' => 'grades'
                        )
                    )
                ),
                'indexes' => array(
                    new Index('PRIMARY', array('id'), 'PRIMARY'),
                    new Index('topics_ibfk_1', array('exam_id'), null)
                ),
                'references' => array(
                    new Reference(
                        'topics_ibfk_1',
                        array(
                            'referencedSchema' => 'openexam2prod',
                            'referencedTable' => 'exams',
                            'columns' => array('exam_id','exam_id','exam_id'),
                            'referencedColumns' => array('id','id','id'),
                            'onUpdate' => 'RESTRICT',
                            'onDelete' => 'CASCADE'
                        )
                    )
                ),
                'options' => array(
                    'TABLE_TYPE' => 'BASE TABLE',
                    'AUTO_INCREMENT' => '60229',
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
