<?php 

use Phalcon\Db\Column;
use Phalcon\Db\Index;
use Phalcon\Db\Reference;
use Phalcon\Mvc\Model\Migration;

class SettingsMigration_2011 extends Migration
{

    public function up()
    {
        $this->morphTable(
            'settings',
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
                    'user',
                    array(
                        'type' => Column::TYPE_VARCHAR,
                        'notNull' => true,
                        'size' => 60,
                        'after' => 'id'
                    )
                ),
                new Column(
                    'data',
                    array(
                        'type' => Column::TYPE_TEXT,
                        'notNull' => true,
                        'size' => 1,
                        'after' => 'user'
                    )
                )
            ),
            'indexes' => array(
                new Index('PRIMARY', array('id'))
            ),
            'options' => array(
                'TABLE_TYPE' => 'BASE TABLE',
                'AUTO_INCREMENT' => '29239',
                'ENGINE' => 'MyISAM',
                'TABLE_COLLATION' => 'utf8_general_ci'
            )
        )
        );
    }
}
