<?php

use Phalcon\Db\Column;
use Phalcon\Db\Index;
use Phalcon\Db\Reference;
use Phalcon\Mvc\Model\Migration;

class ContributorsMigration_090 extends Migration
{

        public function up()
        {
                $this->morphTable(
                    'contributors', array(
                        'columns'    => array(
                                new Column(
                                    'id', array(
                                        'type'          => Column::TYPE_INTEGER,
                                        'notNull'       => true,
                                        'autoIncrement' => true,
                                        'size'          => 11,
                                        'first'         => true
                                    )
                                ),
                                new Column(
                                    'exam_id', array(
                                        'type'    => Column::TYPE_INTEGER,
                                        'notNull' => true,
                                        'size'    => 11,
                                        'after'   => 'id'
                                    )
                                ),
                                new Column(
                                    'user', array(
                                        'type'    => Column::TYPE_CHAR,
                                        'notNull' => true,
                                        'size'    => 8,
                                        'after'   => 'exam_id'
                                    )
                                )
                        ),
                        'indexes'    => array(
                                new Index('PRIMARY', array('id')),
                                new Index('exam_id', array('exam_id'))
                        ),
                        'references' => array(
                                new Reference('contributors_ibfk_1', array(
                                        'referencedSchema'  => 'openexam',
                                        'referencedTable'   => 'exams',
                                        'columns'           => array('exam_id'),
                                        'referencedColumns' => array('id')
                                    ))
                        ),
                        'options'    => array(
                                'TABLE_TYPE'      => 'BASE TABLE',
                                'AUTO_INCREMENT'  => '64',
                                'ENGINE'          => 'InnoDB',
                                'TABLE_COLLATION' => 'utf8_general_ci'
                        )
                    )
                );
        }

}
