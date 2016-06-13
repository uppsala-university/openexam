<?php

use Phalcon\Db\Column;
use Phalcon\Db\Index;
use Phalcon\Db\Reference;
use Phalcon\Mvc\Model\Migration;

class SchemainfoMigration_120 extends Migration
{

        private static $data = array(
                'major' => 1,
                'minor' => 2
        );

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
        }

        public function afterUp()
        {
                self::$_connection->delete("schemainfo");
                self::$_connection->insert(
                    "schemainfo", array_values(self::$data), array_keys(self::$data)
                );
        }

}
