<?php

use Phalcon\Db\Column;
use Phalcon\Db\Index;
use Phalcon\Mvc\Model\Migration;

/**
 * Class SessionsMigration_210
 */
class SessionsMigration_210 extends Migration {
  /**
   * Define the table structure
   *
   * @return void
   */
  public function morph() {
    $this->morphTable('sessions', [
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
          'session_id',
          [
            'type' => Column::TYPE_VARCHAR,
            'notNull' => true,
            'size' => 35,
            'after' => 'id',
          ]
        ),
        new Column(
          'data',
          [
            'type' => Column::TYPE_TEXT,
            'notNull' => true,
            'size' => 1,
            'after' => 'session_id',
          ]
        ),
        new Column(
          'created',
          [
            'type' => Column::TYPE_INTEGER,
            'unsigned' => true,
            'notNull' => true,
            'size' => 15,
            'after' => 'data',
          ]
        ),
        new Column(
          'updated',
          [
            'type' => Column::TYPE_INTEGER,
            'unsigned' => true,
            'size' => 15,
            'after' => 'created',
          ]
        ),
      ],
      'indexes' => [
        new Index('PRIMARY', ['id'], 'PRIMARY'),
        new Index('session_id', ['session_id'], null),
      ],
      'options' => [
        'TABLE_TYPE' => 'BASE TABLE',
        'AUTO_INCREMENT' => '58',
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
