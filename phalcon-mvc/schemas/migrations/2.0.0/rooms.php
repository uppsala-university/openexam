<?php

use Phalcon\Db\Column;
use Phalcon\Db\Index;
use Phalcon\Db\Reference;
use Phalcon\Mvc\Model\Migration;

class RoomsMigration_200 extends Migration
{

        public function up()
        {
                $this->morphTable(
                    'rooms', array(
                        'columns' => array(
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
                                    'name', array(
                                        'type'    => Column::TYPE_VARCHAR,
                                        'notNull' => true,
                                        'size'    => 25,
                                        'after'   => 'id'
                                    )
                                ),
                                new Column(
                                    'description', array(
                                        'type'  => Column::TYPE_TEXT,
                                        'size'  => 1,
                                        'after' => 'name'
                                    )
                                )
                        ),
                        'indexes' => array(
                                new Index('PRIMARY', array('id'))
                        ),
                        'options' => array(
                                'TABLE_TYPE'      => 'BASE TABLE',
                                'AUTO_INCREMENT'  => '1',
                                'ENGINE'          => 'InnoDB',
                                'TABLE_COLLATION' => 'utf8_general_ci'
                        )
                    )
                );
        }

}
