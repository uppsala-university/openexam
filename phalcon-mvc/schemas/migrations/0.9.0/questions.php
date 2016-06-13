<?php

use Phalcon\Db\Column;
use Phalcon\Db\Index;
use Phalcon\Db\Reference;
use Phalcon\Mvc\Model\Migration;

class QuestionsMigration_090 extends Migration
{

        public function up()
        {
                $this->morphTable(
                    'questions', array(
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
                                    'score', array(
                                        'type'    => Column::TYPE_FLOAT,
                                        'notNull' => true,
                                        'size'    => 1,
                                        'after'   => 'exam_id'
                                    )
                                ),
                                new Column(
                                    'name', array(
                                        'type'    => Column::TYPE_VARCHAR,
                                        'notNull' => true,
                                        'size'    => 30,
                                        'after'   => 'score'
                                    )
                                ),
                                new Column(
                                    'quest', array(
                                        'type'    => Column::TYPE_TEXT,
                                        'notNull' => true,
                                        'size'    => 1,
                                        'after'   => 'name'
                                    )
                                ),
                                new Column(
                                    'user', array(
                                        'type'    => Column::TYPE_CHAR,
                                        'notNull' => true,
                                        'size'    => 8,
                                        'after'   => 'quest'
                                    )
                                ),
                                new Column(
                                    'video', array(
                                        'type'  => Column::TYPE_VARCHAR,
                                        'size'  => 512,
                                        'after' => 'user'
                                    )
                                ),
                                new Column(
                                    'image', array(
                                        'type'  => Column::TYPE_VARCHAR,
                                        'size'  => 512,
                                        'after' => 'video'
                                    )
                                ),
                                new Column(
                                    'audio', array(
                                        'type'  => Column::TYPE_VARCHAR,
                                        'size'  => 512,
                                        'after' => 'image'
                                    )
                                ),
                                new Column(
                                    'type', array(
                                        'type'    => Column::TYPE_CHAR,
                                        'notNull' => true,
                                        'size'    => 1,
                                        'after'   => 'audio'
                                    )
                                ),
                                new Column(
                                    'status', array(
                                        'type'    => Column::TYPE_CHAR,
                                        'notNull' => true,
                                        'size'    => 1,
                                        'after'   => 'type'
                                    )
                                ),
                                new Column(
                                    'comment', array(
                                        'type'  => Column::TYPE_TEXT,
                                        'size'  => 1,
                                        'after' => 'status'
                                    )
                                )
                        ),
                        'indexes'    => array(
                                new Index('PRIMARY', array('id')),
                                new Index('exam_id', array('exam_id'))
                        ),
                        'references' => array(
                                new Reference('questions_ibfk_1', array(
                                        'referencedSchema'  => 'openexam',
                                        'referencedTable'   => 'exams',
                                        'columns'           => array('exam_id'),
                                        'referencedColumns' => array('id')
                                    ))
                        ),
                        'options'    => array(
                                'TABLE_TYPE'      => 'BASE TABLE',
                                'AUTO_INCREMENT'  => '452',
                                'ENGINE'          => 'InnoDB',
                                'TABLE_COLLATION' => 'utf8_general_ci'
                        )
                    )
                );
        }

}
