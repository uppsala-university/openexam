<?php

use Phalcon\Db\Column;
use Phalcon\Db\Index;
use Phalcon\Db\Reference;
use Phalcon\Mvc\Model\Migration;

class ComputersMigration_100 extends Migration
{

        public function up()
        {
                $this->morphTable(
                    'computers', array(
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
                                    'room_id', array(
                                        'type'  => Column::TYPE_INTEGER,
                                        'size'  => 11,
                                        'after' => 'id'
                                    )
                                ),
                                new Column(
                                    'hostname', array(
                                        'type'  => Column::TYPE_VARCHAR,
                                        'size'  => 100,
                                        'after' => 'room_id'
                                    )
                                ),
                                new Column(
                                    'ipaddr', array(
                                        'type'    => Column::TYPE_VARCHAR,
                                        'notNull' => true,
                                        'size'    => 45,
                                        'after'   => 'hostname'
                                    )
                                ),
                                new Column(
                                    'port', array(
                                        'type'    => Column::TYPE_INTEGER,
                                        'notNull' => true,
                                        'size'    => 11,
                                        'after'   => 'ipaddr'
                                    )
                                ),
                                new Column(
                                    'password', array(
                                        'type'    => Column::TYPE_VARCHAR,
                                        'notNull' => true,
                                        'size'    => 32,
                                        'after'   => 'port'
                                    )
                                ),
                                new Column(
                                    'created', array(
                                        'type'    => Column::TYPE_DATETIME,
                                        'notNull' => true,
                                        'size'    => 1,
                                        'after'   => 'password'
                                    )
                                ),
                                new Column(
                                    'updated', array(
                                        'type'    => Column::TYPE_DATE,
                                        'notNull' => true,
                                        'size'    => 1,
                                        'after'   => 'created'
                                    )
                                )
                        ),
                        'indexes'    => array(
                                new Index('PRIMARY', array('id')),
                                new Index('room_id', array('room_id'))
                        ),
                        'references' => array(
                                new Reference('computers_ibfk_1', array(
                                        'referencedSchema'  => 'openexam',
                                        'referencedTable'   => 'rooms',
                                        'columns'           => array('room_id'),
                                        'referencedColumns' => array('id')
                                    ))
                        ),
                        'options'    => array(
                                'TABLE_TYPE'      => 'BASE TABLE',
                                'AUTO_INCREMENT'  => '1',
                                'ENGINE'          => 'InnoDB',
                                'TABLE_COLLATION' => 'utf8_general_ci'
                        )
                    )
                );
        }

}
