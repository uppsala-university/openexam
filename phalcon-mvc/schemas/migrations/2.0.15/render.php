<?php 

use Phalcon\Db\Column;
use Phalcon\Db\Index;
use Phalcon\Db\Reference;
use Phalcon\Mvc\Model\Migration;

/**
 * Class RenderMigration_2015
 */
class RenderMigration_2015 extends Migration
{
    /**
     * Define the table structure
     *
     * @return void
     */
    public function morph()
    {
        $this->morphTable('render', array(
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
                        'queued',
                        array(
                            'type' => Column::TYPE_TIMESTAMP,
                            'default' => "CURRENT_TIMESTAMP",
                            'notNull' => true,
                            'size' => 1,
                            'after' => 'exam_id'
                        )
                    ),
                    new Column(
                        'finish',
                        array(
                            'type' => Column::TYPE_DATETIME,
                            'size' => 1,
                            'after' => 'queued'
                        )
                    ),
                    new Column(
                        'user',
                        array(
                            'type' => Column::TYPE_VARCHAR,
                            'notNull' => true,
                            'size' => 60,
                            'after' => 'finish'
                        )
                    ),
                    new Column(
                        'url',
                        array(
                            'type' => Column::TYPE_VARCHAR,
                            'notNull' => true,
                            'size' => 300,
                            'after' => 'user'
                        )
                    ),
                    new Column(
                        'path',
                        array(
                            'type' => Column::TYPE_VARCHAR,
                            'size' => 120,
                            'after' => 'url'
                        )
                    ),
                    new Column(
                        'wait',
                        array(
                            'type' => Column::TYPE_INTEGER,
                            'default' => "0",
                            'notNull' => true,
                            'size' => 11,
                            'after' => 'path'
                        )
                    ),
                    new Column(
                        'type',
                        array(
                            'type' => Column::TYPE_CHAR,
                            'default' => "result",
                            'notNull' => true,
                            'size' => 1,
                            'after' => 'wait'
                        )
                    ),
                    new Column(
                        'status',
                        array(
                            'type' => Column::TYPE_CHAR,
                            'default' => "missing",
                            'notNull' => true,
                            'size' => 1,
                            'after' => 'type'
                        )
                    ),
                    new Column(
                        'message',
                        array(
                            'type' => Column::TYPE_VARCHAR,
                            'size' => 100,
                            'after' => 'status'
                        )
                    )
                ),
                'indexes' => array(
                    new Index('PRIMARY', array('id'), 'PRIMARY'),
                    new Index('exam_id', array('exam_id'), null)
                ),
                'references' => array(
                    new Reference(
                        'render_ibfk_1',
                        array(
                            'referencedSchema' => 'openexam2prod',
                            'referencedTable' => 'exams',
                            'columns' => array('exam_id'),
                            'referencedColumns' => array('id'),
                            'onUpdate' => 'RESTRICT',
                            'onDelete' => 'CASCADE'
                        )
                    )
                ),
                'options' => array(
                    'TABLE_TYPE' => 'BASE TABLE',
                    'AUTO_INCREMENT' => '52',
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
