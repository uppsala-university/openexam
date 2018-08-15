<?php

use Phalcon\Db\Column;
use Phalcon\Db\Index;
use Phalcon\Db\Reference;
use Phalcon\Mvc\Model\Migration;

/**
 * Class ComputersMigration_210
 */
class ComputersMigration_210 extends Migration {
  /**
   * Define the table structure
   *
   * @return void
   */
  public function morph() {
    $this->morphTable('computers', [
      'columns' => [
        new Column(
          'id',
          [
            'type' => Column::TYPE_INTEGER,
            'notNull' => true,
            'autoIncrement' => true,
            'size' => 11,
            'first' => true,
          ]
        ),
        new Column(
          'room_id',
          [
            'type' => Column::TYPE_INTEGER,
            'default' => "0",
            'size' => 11,
            'after' => 'id',
          ]
        ),
        new Column(
          'hostname',
          [
            'type' => Column::TYPE_VARCHAR,
            'size' => 100,
            'after' => 'room_id',
          ]
        ),
        new Column(
          'ipaddr',
          [
            'type' => Column::TYPE_VARCHAR,
            'notNull' => true,
            'size' => 45,
            'after' => 'hostname',
          ]
        ),
        new Column(
          'port',
          [
            'type' => Column::TYPE_INTEGER,
            'notNull' => true,
            'size' => 11,
            'after' => 'ipaddr',
          ]
        ),
        new Column(
          'password',
          [
            'type' => Column::TYPE_VARCHAR,
            'notNull' => true,
            'size' => 32,
            'after' => 'port',
          ]
        ),
        new Column(
          'created',
          [
            'type' => Column::TYPE_DATETIME,
            'notNull' => true,
            'size' => 1,
            'after' => 'password',
          ]
        ),
        new Column(
          'updated',
          [
            'type' => Column::TYPE_TIMESTAMP,
            'default' => "CURRENT_TIMESTAMP",
            'notNull' => true,
            'size' => 1,
            'after' => 'created',
          ]
        ),
      ],
      'indexes' => [
        new Index('PRIMARY', ['id'], 'PRIMARY'),
        new Index('computers_ibfk_1', ['room_id'], null),
        new Index('hostname', ['hostname'], null),
        new Index('ipaddr', ['ipaddr'], null),
      ],
      'references' => [
        new Reference(
          'computers_ibfk_1',
          [
            'referencedTable' => 'rooms',
            'referencedSchema' => 'openexam',
            'columns' => ['room_id'],
            'referencedColumns' => ['id'],
            'onUpdate' => 'RESTRICT',
            'onDelete' => 'CASCADE',
          ]
        ),
      ],
      'options' => [
        'TABLE_TYPE' => 'BASE TABLE',
        'AUTO_INCREMENT' => '1',
        'ENGINE' => 'InnoDB',
        'TABLE_COLLATION' => 'utf8_general_ci',
      ],
    ]
    );
  }

  /**
   * Run the migrations
   *
   * @return void
   */
  public function up() {

  }

  /**
   * Reverse the migrations
   *
   * @return void
   */
  public function down() {

  }

}
