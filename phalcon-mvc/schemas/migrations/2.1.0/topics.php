<?php

use Phalcon\Db\Column;
use Phalcon\Db\Index;
use Phalcon\Db\Reference;
use Phalcon\Mvc\Model\Migration;

/**
 * Class TopicsMigration_210
 */
class TopicsMigration_210 extends Migration {
  /**
   * Define the table structure
   *
   * @return void
   */
  public function morph() {
    $this->morphTable('topics', [
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
          'slot',
          [
            'type' => Column::TYPE_INTEGER,
            'default' => "0",
            'size' => 11,
            'after' => 'exam_id',
          ]
        ),
        new Column(
          'uuid',
          [
            'type' => Column::TYPE_VARCHAR,
            'size' => 36,
            'after' => 'slot',
          ]
        ),
        new Column(
          'name',
          [
            'type' => Column::TYPE_VARCHAR,
            'notNull' => true,
            'size' => 50,
            'after' => 'uuid',
          ]
        ),
        new Column(
          'randomize',
          [
            'type' => Column::TYPE_INTEGER,
            'default' => "0",
            'notNull' => true,
            'size' => 11,
            'after' => 'name',
          ]
        ),
        new Column(
          'grades',
          [
            'type' => Column::TYPE_VARCHAR,
            'size' => 500,
            'after' => 'randomize',
          ]
        ),
        new Column(
          'depend',
          [
            'type' => Column::TYPE_VARCHAR,
            'size' => 500,
            'after' => 'grades',
          ]
        ),
      ],
      'indexes' => [
        new Index('PRIMARY', ['id'], 'PRIMARY'),
        new Index('topics_ibfk_1', ['exam_id'], null),
      ],
      'references' => [
        new Reference(
          'topics_ibfk_1',
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
        'AUTO_INCREMENT' => '2',
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
