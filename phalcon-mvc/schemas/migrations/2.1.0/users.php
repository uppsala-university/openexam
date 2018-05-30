<?php 

use Phalcon\Db\Column;
use Phalcon\Db\Index;
use Phalcon\Db\Reference;
use Phalcon\Mvc\Model\Migration;

/**
 * Class UsersMigration_210
 */
class UsersMigration_210 extends Migration
{
    /**
     * Define the table structure
     *
     * @return void
     */
    public function morph()
    {
        $this->morphTable('users', [
                'columns' => [
                    new Column(
                        'id',
                        [
                            'type' => Column::TYPE_INTEGER,
                            'notNull' => true,
                            'autoIncrement' => true,
                            'size' => 11,
                            'first' => true
                        ]
                    ),
                    new Column(
                        'principal',
                        [
                            'type' => Column::TYPE_VARCHAR,
                            'notNull' => true,
                            'size' => 60,
                            'after' => 'id'
                        ]
                    ),
                    new Column(
                        'uid',
                        [
                            'type' => Column::TYPE_VARCHAR,
                            'notNull' => true,
                            'size' => 20,
                            'after' => 'principal'
                        ]
                    ),
                    new Column(
                        'domain',
                        [
                            'type' => Column::TYPE_VARCHAR,
                            'notNull' => true,
                            'size' => 40,
                            'after' => 'uid'
                        ]
                    ),
                    new Column(
                        'given_name',
                        [
                            'type' => Column::TYPE_VARCHAR,
                            'notNull' => true,
                            'size' => 30,
                            'after' => 'domain'
                        ]
                    ),
                    new Column(
                        'sn',
                        [
                            'type' => Column::TYPE_VARCHAR,
                            'notNull' => true,
                            'size' => 30,
                            'after' => 'given_name'
                        ]
                    ),
                    new Column(
                        'display_name',
                        [
                            'type' => Column::TYPE_VARCHAR,
                            'notNull' => true,
                            'size' => 60,
                            'after' => 'sn'
                        ]
                    ),
                    new Column(
                        'cn',
                        [
                            'type' => Column::TYPE_VARCHAR,
                            'size' => 60,
                            'after' => 'display_name'
                        ]
                    ),
                    new Column(
                        'mail',
                        [
                            'type' => Column::TYPE_VARCHAR,
                            'notNull' => true,
                            'size' => 60,
                            'after' => 'cn'
                        ]
                    ),
                    new Column(
                        'pnr',
                        [
                            'type' => Column::TYPE_CHAR,
                            'size' => 12,
                            'after' => 'mail'
                        ]
                    ),
                    new Column(
                        'o',
                        [
                            'type' => Column::TYPE_VARCHAR,
                            'size' => 40,
                            'after' => 'pnr'
                        ]
                    ),
                    new Column(
                        'c',
                        [
                            'type' => Column::TYPE_CHAR,
                            'size' => 2,
                            'after' => 'o'
                        ]
                    ),
                    new Column(
                        'co',
                        [
                            'type' => Column::TYPE_VARCHAR,
                            'size' => 30,
                            'after' => 'c'
                        ]
                    ),
                    new Column(
                        'acronym',
                        [
                            'type' => Column::TYPE_VARCHAR,
                            'size' => 10,
                            'after' => 'co'
                        ]
                    ),
                    new Column(
                        'home',
                        [
                            'type' => Column::TYPE_VARCHAR,
                            'size' => 10,
                            'after' => 'acronym'
                        ]
                    ),
                    new Column(
                        'assurance',
                        [
                            'type' => Column::TYPE_VARCHAR,
                            'size' => 200,
                            'after' => 'home'
                        ]
                    ),
                    new Column(
                        'affiliation',
                        [
                            'type' => Column::TYPE_VARCHAR,
                            'size' => 200,
                            'after' => 'assurance'
                        ]
                    ),
                    new Column(
                        'source',
                        [
                            'type' => Column::TYPE_VARCHAR,
                            'notNull' => true,
                            'size' => 10,
                            'after' => 'affiliation'
                        ]
                    ),
                    new Column(
                        'created',
                        [
                            'type' => Column::TYPE_DATETIME,
                            'default' => "CURRENT_TIMESTAMP",
                            'notNull' => true,
                            'size' => 1,
                            'after' => 'source'
                        ]
                    )
                ],
                'indexes' => [
                    new Index('PRIMARY', ['id'], 'PRIMARY'),
                    new Index('principal', ['principal'], null),
                    new Index('given_name', ['given_name'], null),
                    new Index('display_name', ['display_name'], null),
                    new Index('sn', ['sn'], null),
                    new Index('cn', ['cn'], null),
                    new Index('pnr', ['pnr'], null)
                ],
                'options' => [
                    'TABLE_TYPE' => 'BASE TABLE',
                    'AUTO_INCREMENT' => '1',
                    'ENGINE' => 'InnoDB',
                    'TABLE_COLLATION' => 'utf8_general_ci'
                ],
            ]
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
