<?php

use Phalcon\Db\Column;
use Phalcon\Db\Index;
use Phalcon\Db\Reference;
use Phalcon\Mvc\Model\Migration;

class SchemainfoMigration_120 extends Migration
{

        public function up()
        {
                $this->morphTable(
                    'schemainfo', array(
                        'columns' => array(
                                new Column(
                                    'major', array(
                                        'type'    => Column::TYPE_INTEGER,
                                        'notNull' => true,
                                        'size'    => 11,
                                        'first'   => true
                                    )
                                ),
                                new Column(
                                    'minor', array(
                                        'type'    => Column::TYPE_INTEGER,
                                        'notNull' => true,
                                        'size'    => 11,
                                        'after'   => 'major'
                                    )
                                )
                        ),
                        'options' => array(
                                'TABLE_TYPE'      => 'BASE TABLE',
                                'AUTO_INCREMENT'  => '',
                                'ENGINE'          => 'MyISAM',
                                'TABLE_COLLATION' => 'utf8_general_ci'
                        )
                    )
                );
                self::$_connection->update(
                    "schemainfo", array(1, 2), array("major", "minor")
                );
        }

}
