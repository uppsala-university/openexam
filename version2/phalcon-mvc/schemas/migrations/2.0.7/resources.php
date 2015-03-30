<?php 

use Phalcon\Db\Column;
use Phalcon\Db\Index;
use Phalcon\Db\Reference;
use Phalcon\Mvc\Model\Migration;

class ResourcesMigration_207 extends Migration
{

    public function up()
    {
        $this->morphTable(
            'resources',
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
                    'exam_id',
                    array(
                        'type' => Column::TYPE_INTEGER,
                        'notNull' => true,
                        'size' => 11,
                        'after' => 'id'
                    )
                ),
                new Column(
                    'name',
                    array(
                        'type' => Column::TYPE_VARCHAR,
                        'notNull' => true,
                        'size' => 50,
                        'after' => 'exam_id'
                    )
                ),
                new Column(
                    'descr',
                    array(
                        'type' => Column::TYPE_VARCHAR,
                        'size' => 150,
                        'after' => 'name'
                    )
                ),
                new Column(
                    'path',
                    array(
                        'type' => Column::TYPE_VARCHAR,
                        'notNull' => true,
                        'size' => 120,
                        'after' => 'descr'
                    )
                ),
                new Column(
                    'type',
                    array(
                        'type' => Column::TYPE_VARCHAR,
                        'notNull' => true,
                        'size' => 25,
                        'after' => 'path'
                    )
                ),
                new Column(
                    'subtype',
                    array(
                        'type' => Column::TYPE_VARCHAR,
                        'notNull' => true,
                        'size' => 25,
                        'after' => 'type'
                    )
                ),
                new Column(
                    'user',
                    array(
                        'type' => Column::TYPE_VARCHAR,
                        'notNull' => true,
                        'size' => 60,
                        'after' => 'subtype'
                    )
                ),
                new Column(
                    'shared',
                    array(
                        'type' => Column::TYPE_CHAR,
                        'size' => 1,
                        'after' => 'user'
                    )
                )
            ),
            'indexes' => array(
                new Index('PRIMARY', array('id')),
                new Index('exam_id', array('exam_id'))
            ),
            'references' => array(
                new Reference('resources_ibfk_1', array(
                    'referencedSchema' => 'openexam',
                    'referencedTable' => 'exams',
                    'columns' => array('exam_id'),
                    'referencedColumns' => array('id')
                ))
            ),
            'options' => array(
                'TABLE_TYPE' => 'BASE TABLE',
                'AUTO_INCREMENT' => '1',
                'ENGINE' => 'InnoDB',
                'TABLE_COLLATION' => 'utf8_general_ci'
            )
        )
        );
    }
}
