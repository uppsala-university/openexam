<?php 

use Phalcon\Db\Column;
use Phalcon\Db\Index;
use Phalcon\Db\Reference;
use Phalcon\Mvc\Model\Migration;

class SessionsMigration_202 extends Migration
{

    public function up()
    {
        $this->morphTable(
            'sessions',
            array(
            'columns' => array(
                new Column(
                    'session_id',
                    array(
                        'type' => Column::TYPE_VARCHAR,
                        'notNull' => true,
                        'size' => 35,
                        'first' => true
                    )
                ),
                new Column(
                    'data',
                    array(
                        'type' => Column::TYPE_TEXT,
                        'notNull' => true,
                        'size' => 1,
                        'after' => 'session_id'
                    )
                ),
                new Column(
                    'created',
                    array(
                        'type' => Column::TYPE_INTEGER,
                        'unsigned' => true,
                        'notNull' => true,
                        'size' => 15,
                        'after' => 'data'
                    )
                ),
                new Column(
                    'updated',
                    array(
                        'type' => Column::TYPE_INTEGER,
                        'unsigned' => true,
                        'size' => 15,
                        'after' => 'created'
                    )
                )
            ),
            'indexes' => array(
                new Index('PRIMARY', array('session_id'))
            ),
            'options' => array(
                'TABLE_TYPE' => 'BASE TABLE',
                'AUTO_INCREMENT' => '',
                'ENGINE' => 'MyISAM',
                'TABLE_COLLATION' => 'utf8_general_ci'
            )
        )
        );
    }
}
