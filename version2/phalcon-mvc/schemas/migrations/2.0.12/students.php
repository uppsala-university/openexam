<?php 

use Phalcon\Db\Column;
use Phalcon\Db\Index;
use Phalcon\Db\Reference;
use Phalcon\Mvc\Model\Migration;

class StudentsMigration_2012 extends Migration
{

    public function up()
    {
        $this->morphTable(
            'students',
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
                    'user',
                    array(
                        'type' => Column::TYPE_VARCHAR,
                        'notNull' => true,
                        'size' => 60,
                        'after' => 'exam_id'
                    )
                ),
                new Column(
                    'code',
                    array(
                        'type' => Column::TYPE_VARCHAR,
                        'notNull' => true,
                        'size' => 15,
                        'after' => 'user'
                    )
                ),
                new Column(
                    'tag',
                    array(
                        'type' => Column::TYPE_VARCHAR,
                        'size' => 30,
                        'after' => 'code'
                    )
                ),
                new Column(
                    'starttime',
                    array(
                        'type' => Column::TYPE_DATETIME,
                        'size' => 1,
                        'after' => 'tag'
                    )
                ),
                new Column(
                    'endtime',
                    array(
                        'type' => Column::TYPE_DATETIME,
                        'size' => 1,
                        'after' => 'starttime'
                    )
                ),
                new Column(
                    'finished',
                    array(
                        'type' => Column::TYPE_DATETIME,
                        'size' => 1,
                        'after' => 'endtime'
                    )
                )
            ),
            'indexes' => array(
                new Index('PRIMARY', array('id')),
                new Index('exam_id', array('exam_id'))
            ),
            'references' => array(
                new Reference('students_ibfk_1', array(
                    'referencedSchema' => 'openexam2prod',
                    'referencedTable' => 'exams',
                    'columns' => array('exam_id'),
                    'referencedColumns' => array('id')
                ))
            ),
            'options' => array(
                'TABLE_TYPE' => 'BASE TABLE',
                'AUTO_INCREMENT' => '43467',
                'ENGINE' => 'InnoDB',
                'TABLE_COLLATION' => 'utf8_general_ci'
            )
        )
        );
    }
}
