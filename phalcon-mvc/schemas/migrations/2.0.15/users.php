<?php 

use Phalcon\Db\Column;
use Phalcon\Db\Index;
use Phalcon\Db\Reference;
use Phalcon\Mvc\Model\Migration;

/**
 * Class UsersMigration_2015
 */
class UsersMigration_2015 extends Migration
{
    /**
     * Define the table structure
     *
     * @return void
     */
    public function morph()
    {
        $this->morphTable('users', array(
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
                        'principal',
                        array(
                            'type' => Column::TYPE_VARCHAR,
                            'notNull' => true,
                            'size' => 60,
                            'after' => 'id'
                        )
                    ),
                    new Column(
                        'uid',
                        array(
                            'type' => Column::TYPE_VARCHAR,
                            'notNull' => true,
                            'size' => 20,
                            'after' => 'principal'
                        )
                    ),
                    new Column(
                        'domain',
                        array(
                            'type' => Column::TYPE_VARCHAR,
                            'notNull' => true,
                            'size' => 40,
                            'after' => 'uid'
                        )
                    ),
                    new Column(
                        'given_name',
                        array(
                            'type' => Column::TYPE_VARCHAR,
                            'notNull' => true,
                            'size' => 30,
                            'after' => 'domain'
                        )
                    ),
                    new Column(
                        'sn',
                        array(
                            'type' => Column::TYPE_VARCHAR,
                            'notNull' => true,
                            'size' => 30,
                            'after' => 'given_name'
                        )
                    ),
                    new Column(
                        'display_name',
                        array(
                            'type' => Column::TYPE_VARCHAR,
                            'notNull' => true,
                            'size' => 60,
                            'after' => 'sn'
                        )
                    ),
                    new Column(
                        'cn',
                        array(
                            'type' => Column::TYPE_VARCHAR,
                            'size' => 60,
                            'after' => 'display_name'
                        )
                    ),
                    new Column(
                        'mail',
                        array(
                            'type' => Column::TYPE_VARCHAR,
                            'notNull' => true,
                            'size' => 60,
                            'after' => 'cn'
                        )
                    ),
                    new Column(
                        'pnr',
                        array(
                            'type' => Column::TYPE_CHAR,
                            'size' => 12,
                            'after' => 'mail'
                        )
                    ),
                    new Column(
                        'o',
                        array(
                            'type' => Column::TYPE_VARCHAR,
                            'size' => 40,
                            'after' => 'pnr'
                        )
                    ),
                    new Column(
                        'c',
                        array(
                            'type' => Column::TYPE_CHAR,
                            'size' => 2,
                            'after' => 'o'
                        )
                    ),
                    new Column(
                        'co',
                        array(
                            'type' => Column::TYPE_VARCHAR,
                            'size' => 30,
                            'after' => 'c'
                        )
                    ),
                    new Column(
                        'acronym',
                        array(
                            'type' => Column::TYPE_VARCHAR,
                            'size' => 10,
                            'after' => 'co'
                        )
                    ),
                    new Column(
                        'home',
                        array(
                            'type' => Column::TYPE_VARCHAR,
                            'size' => 10,
                            'after' => 'acronym'
                        )
                    ),
                    new Column(
                        'assurance',
                        array(
                            'type' => Column::TYPE_VARCHAR,
                            'size' => 200,
                            'after' => 'home'
                        )
                    ),
                    new Column(
                        'affiliation',
                        array(
                            'type' => Column::TYPE_VARCHAR,
                            'size' => 200,
                            'after' => 'assurance'
                        )
                    ),
                    new Column(
                        'source',
                        array(
                            'type' => Column::TYPE_VARCHAR,
                            'notNull' => true,
                            'size' => 10,
                            'after' => 'affiliation'
                        )
                    ),
                    new Column(
                        'created',
                        array(
                            'type' => Column::TYPE_DATETIME,
                            'default' => "CURRENT_TIMESTAMP",
                            'notNull' => true,
                            'size' => 1,
                            'after' => 'source'
                        )
                    )
                ),
                'indexes' => array(
                    new Index('PRIMARY', array('id'), 'PRIMARY')
                ),
                'options' => array(
                    'TABLE_TYPE' => 'BASE TABLE',
                    'AUTO_INCREMENT' => '120',
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
