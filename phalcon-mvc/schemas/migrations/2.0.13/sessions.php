<?php 

use Phalcon\Db\Column;
use Phalcon\Db\Index;
use Phalcon\Db\Reference;
use Phalcon\Mvc\Model\Migration;

/**
 * Class SessionsMigration_2013
 */
class SessionsMigration_2013 extends Migration
{
    /**
     * Define the table structure
     *
     * @return void
     */
    public function morph()
    {
        $this->morphTable('sessions', array(
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
                        'session_id',
                        array(
                            'type' => Column::TYPE_VARCHAR,
                            'notNull' => true,
                            'size' => 35,
                            'after' => 'id'
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
                    new Index('PRIMARY', array('id'), 'PRIMARY'),
                    new Index('session_id', array('session_id'), null)
                ),
                'options' => array(
                    'TABLE_TYPE' => 'BASE TABLE',
                    'AUTO_INCREMENT' => '76578',
                    'ENGINE' => 'MyISAM',
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
