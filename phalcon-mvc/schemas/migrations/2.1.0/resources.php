<?php

use Phalcon\Db\Column;
use Phalcon\Db\Index;
use Phalcon\Db\Reference;
use Phalcon\Mvc\Model\Migration;

/**
 * Class ResourcesMigration_210
 */
class ResourcesMigration_210 extends Migration {
  /**
   * Define the table structure
   *
   * @return void
   */
  public function morph() {
    $this->morphTable('resources', [
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
          'exam_id',
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
            'after' => 'exam_id',
          ]
        ),
        new Column(
          'descr',
          [
            'type' => Column::TYPE_VARCHAR,
            'size' => 150,
            'after' => 'name',
          ]
        ),
        new Column(
          'path',
          [
            'type' => Column::TYPE_VARCHAR,
            'notNull' => true,
            'size' => 120,
            'after' => 'descr',
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
        new Column(
          'user',
          [
            'type' => Column::TYPE_VARCHAR,
            'notNull' => true,
            'size' => 60,
            'after' => 'subtype',
          ]
        ),
        new Column(
          'shared',
          [
            'type' => Column::TYPE_CHAR,
            'default' => "exam",
            'size' => 1,
            'after' => 'user',
          ]
        ),
      ],
      'indexes' => [
        new Index('PRIMARY', ['id'], 'PRIMARY'),
        new Index('exam_id', ['exam_id'], null),
      ],
      'references' => [
        new Reference(
          'resources_ibfk_1',
          [
            'referencedTable' => 'exams',
            'referencedSchema' => 'openexam',
            'columns' => ['exam_id'],
            'referencedColumns' => ['id'],
            'onUpdate' => 'RESTRICT',
            'onDelete' => 'CASCADE',
          ]
        ),
      ],
      'options' => [
        'TABLE_TYPE' => 'BASE TABLE',
        'AUTO_INCREMENT' => '3',
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
