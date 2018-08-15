<?php

use Phalcon\Db\Column;
use Phalcon\Db\Index;
use Phalcon\Mvc\Model\Migration;

/**
 * Class PerformanceMigration_210
 */
class PerformanceMigration_210 extends Migration {
  /**
   * Define the table structure
   *
   * @return void
   */
  public function morph() {
    $this->morphTable('performance', [
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
          'mode',
          [
            'type' => Column::TYPE_CHAR,
            'notNull' => true,
            'size' => 1,
            'after' => 'id',
          ]
        ),
        new Column(
          'source',
          [
            'type' => Column::TYPE_VARCHAR,
            'size' => 30,
            'after' => 'mode',
          ]
        ),
        new Column(
          'time',
          [
            'type' => Column::TYPE_TIMESTAMP,
            'default' => "CURRENT_TIMESTAMP",
            'notNull' => true,
            'size' => 1,
            'after' => 'source',
          ]
        ),
        new Column(
          'host',
          [
            'type' => Column::TYPE_VARCHAR,
            'notNull' => true,
            'size' => 60,
            'after' => 'time',
          ]
        ),
        new Column(
          'addr',
          [
            'type' => Column::TYPE_VARCHAR,
            'notNull' => true,
            'size' => 60,
            'after' => 'host',
          ]
        ),
        new Column(
          'milestone',
          [
            'type' => Column::TYPE_CHAR,
            'size' => 1,
            'after' => 'addr',
          ]
        ),
        new Column(
          'data',
          [
            'type' => Column::TYPE_VARCHAR,
            'notNull' => true,
            'size' => 1024,
            'after' => 'milestone',
          ]
        ),
      ],
      'indexes' => [
        new Index('PRIMARY', ['id'], 'PRIMARY'),
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
