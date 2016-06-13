<?php 

use Phalcon\Db\Column;
use Phalcon\Db\Index;
use Phalcon\Db\Reference;
use Phalcon\Mvc\Model\Migration;

class LocksMigration_209 extends Migration
{

    public function up()
    {
        $this->morphTable(
            'locks',
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
                    'student_id',
                    array(
                        'type' => Column::TYPE_INTEGER,
                        'notNull' => true,
                        'size' => 11,
                        'after' => 'id'
                    )
                ),
                new Column(
                    'computer_id',
                    array(
                        'type' => Column::TYPE_INTEGER,
                        'notNull' => true,
                        'size' => 11,
                        'after' => 'student_id'
                    )
                ),
                new Column(
                    'exam_id',
                    array(
                        'type' => Column::TYPE_INTEGER,
                        'notNull' => true,
                        'size' => 11,
                        'after' => 'computer_id'
                    )
                ),
                new Column(
                    'aquired',
                    array(
                        'type' => Column::TYPE_DATE,
                        'notNull' => true,
                        'size' => 1,
                        'after' => 'exam_id'
                    )
                ),
                new Column(
                    'status',
                    array(
                        'type' => Column::TYPE_CHAR,
                        'notNull' => true,
                        'size' => 1,
                        'after' => 'aquired'
                    )
                )
            ),
            'indexes' => array(
                new Index('PRIMARY', array('id')),
                new Index('locks_ibfk_1', array('computer_id')),
                new Index('locks_ibfk_2', array('exam_id')),
                new Index('student_id', array('student_id'))
            ),
            'references' => array(
                new Reference('locks_ibfk_1', array(
                    'referencedSchema' => 'openexam',
                    'referencedTable' => 'computers',
                    'columns' => array('computer_id'),
                    'referencedColumns' => array('id')
                )),
                new Reference('locks_ibfk_2', array(
                    'referencedSchema' => 'openexam',
                    'referencedTable' => 'exams',
                    'columns' => array('exam_id'),
                    'referencedColumns' => array('id')
                )),
                new Reference('locks_ibfk_3', array(
                    'referencedSchema' => 'openexam',
                    'referencedTable' => 'students',
                    'columns' => array('student_id'),
                    'referencedColumns' => array('id')
                ))
            ),
            'options' => array(
                'TABLE_TYPE' => 'BASE TABLE',
                'AUTO_INCREMENT' => '402',
                'ENGINE' => 'InnoDB',
                'TABLE_COLLATION' => 'utf8_general_ci'
            )
        )
        );
    }
}
