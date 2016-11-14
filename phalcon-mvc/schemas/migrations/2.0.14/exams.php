<?php 

use Phalcon\Db\Column;
use Phalcon\Db\Index;
use Phalcon\Db\Reference;
use Phalcon\Mvc\Model\Migration;

/**
 * Class ExamsMigration_2014
 */
class ExamsMigration_2014 extends Migration
{
    /**
     * Define the table structure
     *
     * @return void
     */
    public function morph()
    {
        $this->morphTable('exams', array(
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
                        'name',
                        array(
                            'type' => Column::TYPE_VARCHAR,
                            'notNull' => true,
                            'size' => 200,
                            'after' => 'id'
                        )
                    ),
                    new Column(
                        'descr',
                        array(
                            'type' => Column::TYPE_TEXT,
                            'size' => 1,
                            'after' => 'name'
                        )
                    ),
                    new Column(
                        'starttime',
                        array(
                            'type' => Column::TYPE_DATETIME,
                            'size' => 1,
                            'after' => 'descr'
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
                        'created',
                        array(
                            'type' => Column::TYPE_DATETIME,
                            'notNull' => true,
                            'size' => 1,
                            'after' => 'endtime'
                        )
                    ),
                    new Column(
                        'updated',
                        array(
                            'type' => Column::TYPE_TIMESTAMP,
                            'default' => "CURRENT_TIMESTAMP",
                            'notNull' => true,
                            'size' => 1,
                            'after' => 'created'
                        )
                    ),
                    new Column(
                        'creator',
                        array(
                            'type' => Column::TYPE_VARCHAR,
                            'notNull' => true,
                            'size' => 60,
                            'after' => 'updated'
                        )
                    ),
                    new Column(
                        'details',
                        array(
                            'type' => Column::TYPE_INTEGER,
                            'default' => "3",
                            'notNull' => true,
                            'size' => 11,
                            'after' => 'creator'
                        )
                    ),
                    new Column(
                        'decoded',
                        array(
                            'type' => Column::TYPE_CHAR,
                            'default' => "N",
                            'notNull' => true,
                            'size' => 1,
                            'after' => 'details'
                        )
                    ),
                    new Column(
                        'published',
                        array(
                            'type' => Column::TYPE_CHAR,
                            'default' => "N",
                            'size' => 1,
                            'after' => 'decoded'
                        )
                    ),
                    new Column(
                        'orgunit',
                        array(
                            'type' => Column::TYPE_VARCHAR,
                            'notNull' => true,
                            'size' => 150,
                            'after' => 'published'
                        )
                    ),
                    new Column(
                        'orgdiv',
                        array(
                            'type' => Column::TYPE_VARCHAR,
                            'notNull' => true,
                            'size' => 100,
                            'after' => 'orgunit'
                        )
                    ),
                    new Column(
                        'orgdep',
                        array(
                            'type' => Column::TYPE_VARCHAR,
                            'size' => 100,
                            'after' => 'orgdiv'
                        )
                    ),
                    new Column(
                        'orggrp',
                        array(
                            'type' => Column::TYPE_VARCHAR,
                            'size' => 100,
                            'after' => 'orgdep'
                        )
                    ),
                    new Column(
                        'grades',
                        array(
                            'type' => Column::TYPE_VARCHAR,
                            'notNull' => true,
                            'size' => 200,
                            'after' => 'orggrp'
                        )
                    ),
                    new Column(
                        'course',
                        array(
                            'type' => Column::TYPE_VARCHAR,
                            'size' => 20,
                            'after' => 'grades'
                        )
                    ),
                    new Column(
                        'code',
                        array(
                            'type' => Column::TYPE_VARCHAR,
                            'size' => 20,
                            'after' => 'course'
                        )
                    ),
                    new Column(
                        'testcase',
                        array(
                            'type' => Column::TYPE_CHAR,
                            'default' => "N",
                            'notNull' => true,
                            'size' => 1,
                            'after' => 'code'
                        )
                    ),
                    new Column(
                        'lockdown',
                        array(
                            'type' => Column::TYPE_VARCHAR,
                            'default' => "{"enable":true}",
                            'notNull' => true,
                            'size' => 500,
                            'after' => 'testcase'
                        )
                    )
                ),
                'indexes' => array(
                    new Index('PRIMARY', array('id'), 'PRIMARY')
                ),
                'options' => array(
                    'TABLE_TYPE' => 'BASE TABLE',
                    'AUTO_INCREMENT' => '29803',
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
