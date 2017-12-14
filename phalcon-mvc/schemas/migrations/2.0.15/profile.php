<?php 

use Phalcon\Db\Column;
use Phalcon\Db\Index;
use Phalcon\Db\Reference;
use Phalcon\Mvc\Model\Migration;

/**
 * Class ProfileMigration_2015
 */
class ProfileMigration_2015 extends Migration
{
    /**
     * Define the table structure
     *
     * @return void
     */
    public function morph()
    {
        $this->morphTable('profile', array(
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
                        'stamp',
                        array(
                            'type' => Column::TYPE_DATETIME,
                            'default' => "CURRENT_TIMESTAMP",
                            'notNull' => true,
                            'size' => 1,
                            'after' => 'id'
                        )
                    ),
                    new Column(
                        'request',
                        array(
                            'type' => Column::TYPE_VARCHAR,
                            'notNull' => true,
                            'size' => 255,
                            'after' => 'stamp'
                        )
                    ),
                    new Column(
                        'name',
                        array(
                            'type' => Column::TYPE_VARCHAR,
                            'notNull' => true,
                            'size' => 15,
                            'after' => 'request'
                        )
                    ),
                    new Column(
                        'peak',
                        array(
                            'type' => Column::TYPE_VARCHAR,
                            'notNull' => true,
                            'size' => 10,
                            'after' => 'name'
                        )
                    ),
                    new Column(
                        'time',
                        array(
                            'type' => Column::TYPE_FLOAT,
                            'notNull' => true,
                            'size' => 1,
                            'after' => 'peak'
                        )
                    ),
                    new Column(
                        'host',
                        array(
                            'type' => Column::TYPE_VARCHAR,
                            'size' => 30,
                            'after' => 'time'
                        )
                    ),
                    new Column(
                        'addr',
                        array(
                            'type' => Column::TYPE_VARCHAR,
                            'size' => 46,
                            'after' => 'host'
                        )
                    ),
                    new Column(
                        'server',
                        array(
                            'type' => Column::TYPE_VARCHAR,
                            'size' => 60,
                            'after' => 'addr'
                        )
                    ),
                    new Column(
                        'data',
                        array(
                            'type' => Column::TYPE_VARCHAR,
                            'notNull' => true,
                            'size' => 1024,
                            'after' => 'server'
                        )
                    )
                ),
                'indexes' => array(
                    new Index('PRIMARY', array('id'), 'PRIMARY')
                ),
                'options' => array(
                    'TABLE_TYPE' => 'BASE TABLE',
                    'AUTO_INCREMENT' => '514812',
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
