<?php

use Phalcon\Db\Column;
use Phalcon\Db\Index;
use Phalcon\Db\Reference;
use Phalcon\Mvc\Model\Migration;

class SchemainfoMigration_100 extends Migration
{

        public function up()
        {
                $this->morphTable(
                    'schemainfo', array(
                        'columns' => array(
                                new Column(
                                    'id', array(
                                        'type'    => Column::TYPE_INTEGER,
                                        'notNull' => true,
                                        'size'    => 11,
                                        'first'   => true
                                    )
                                ),
                                new Column(
                                    'major', array(
                                        'type'    => Column::TYPE_INTEGER,
                                        'notNull' => true,
                                        'size'    => 11,
                                        'after'   => 'id'
                                    )
                                ),
                                new Column(
                                    'minor', array(
                                        'type'    => Column::TYPE_INTEGER,
                                        'notNull' => true,
                                        'size'    => 11,
                                        'after'   => 'major'
                                    )
                                ),
                                new Column(
                                    'updated', array(
                                        'type'    => Column::TYPE_DATE,
                                        'notNull' => true,
                                        'size'    => 1,
                                        'after'   => 'minor'
                                    )
                                )
                        ),
                        'indexes' => array(
                                new Index('PRIMARY', array('id'))
                        ),
                        'options' => array(
                                'TABLE_TYPE'      => 'BASE TABLE',
                                'AUTO_INCREMENT'  => '',
                                'ENGINE'          => 'MyISAM',
                                'TABLE_COLLATION' => 'utf8_general_ci'
                        )
                    )
                );
                self::$_connection->insert(
                    "schemainfo", array(1, 0), array("major", "minor")
                );
        }

}
