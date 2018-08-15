<?php

use Phalcon\Db\Column;
use Phalcon\Db\Index;
use Phalcon\Db\Reference;
use Phalcon\Mvc\Model\Migration;

/**
 * Class FilesMigration_210
 */
class FilesMigration_210 extends Migration {
  /**
   * Define the table structure
   *
   * @return void
   */
  public function morph() {
    $this->morphTable('files', [
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
          'answer_id',
          [
            'type' => Column::TYPE_INTEGER,
            'notNull' => true,
            'size' => 11,
            'after' => 'id',
          ]
        ),
        new Column(
          'name',
          [
            'type' => Column::TYPE_VARCHAR,
            'notNull' => true,
            'size' => 50,
            'after' => 'answer_id',
          ]
        ),
        new Column(
          'path',
          [
            'type' => Column::TYPE_VARCHAR,
            'notNull' => true,
            'size' => 120,
            'after' => 'name',
          ]
        ),
        new Column(
          'type',
          [
            'type' => Column::TYPE_VARCHAR,
            'notNull' => true,
            'size' => 25,
            'after' => 'path',
          ]
        ),
        new Column(
          'subtype',
          [
            'type' => Column::TYPE_VARCHAR,
            'notNull' => true,
            'size' => 25,
            'after' => 'type',
          ]
        ),
      ],
      'indexes' => [
        new Index('PRIMARY', ['id'], 'PRIMARY'),
        new Index('answer_id', ['answer_id'], null),
        new Index('name', ['name'], null),
        new Index('path', ['path'], null),
      ],
      'references' => [
        new Reference(
          'files_ibfk_1',
          [
            'referencedTable' => 'answers',
            'referencedSchema' => 'openexam',
            'columns' => ['answer_id'],
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
