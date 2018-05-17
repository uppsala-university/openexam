<?php

use Phalcon\Db\Column;
use Phalcon\Db\Index;
use Phalcon\Mvc\Model\Migration;

/**
 * Class ProfileMigration_216
 */
class ProfileMigration_216 extends Migration {
  /**
   * Define the table structure
   *
   * @return void
   */
  public function morph() {
    $this->morphTable('profile', [
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
          'stamp',
          [
            'type' => Column::TYPE_DATETIME,
            'default' => "CURRENT_TIMESTAMP",
            'notNull' => true,
            'size' => 1,
            'after' => 'id',
          ]
        ),
        new Column(
          'request',
          [
            'type' => Column::TYPE_VARCHAR,
            'notNull' => true,
            'size' => 255,
            'after' => 'stamp',
          ]
        ),
        new Column(
          'name',
          [
            'type' => Column::TYPE_VARCHAR,
            'notNull' => true,
            'size' => 15,
            'after' => 'request',
          ]
        ),
        new Column(
          'peak',
          [
            'type' => Column::TYPE_VARCHAR,
            'notNull' => true,
            'size' => 10,
            'after' => 'name',
          ]
        ),
        new Column(
          'time',
          [
            'type' => Column::TYPE_FLOAT,
            'notNull' => true,
            'size' => 1,
            'after' => 'peak',
          ]
        ),
        new Column(
          'host',
          [
            'type' => Column::TYPE_VARCHAR,
            'size' => 30,
            'after' => 'time',
          ]
        ),
        new Column(
          'addr',
          [
            'type' => Column::TYPE_VARCHAR,
            'size' => 46,
            'after' => 'host',
          ]
        ),
        new Column(
          'server',
          [
            'type' => Column::TYPE_VARCHAR,
            'size' => 60,
            'after' => 'addr',
          ]
        ),
        new Column(
          'data',
          [
            'type' => Column::TYPE_VARCHAR,
            'notNull' => true,
            'size' => 1024,
            'after' => 'server',
          ]
        ),
      ],
      'indexes' => [
        new Index('PRIMARY', ['id'], 'PRIMARY'),
      ],
      'options' => [
        'TABLE_TYPE' => 'BASE TABLE',
        'AUTO_INCREMENT' => '581521',
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
