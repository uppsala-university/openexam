<?php

use Phalcon\Db\Column;
use Phalcon\Db\Index;
use Phalcon\Db\Reference;
use Phalcon\Mvc\Model\Migration;

/**
 * Class StudentsMigration_210
 */
class StudentsMigration_210 extends Migration {
  /**
   * Define the table structure
   *
   * @return void
   */
  public function morph() {
    $this->morphTable('students', [
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
          'user',
          [
            'type' => Column::TYPE_VARCHAR,
            'notNull' => true,
            'size' => 60,
            'after' => 'exam_id',
          ]
        ),
        new Column(
          'code',
          [
            'type' => Column::TYPE_VARCHAR,
            'notNull' => true,
            'size' => 15,
            'after' => 'user',
          ]
        ),
        new Column(
          'tag',
          [
            'type' => Column::TYPE_VARCHAR,
            'size' => 30,
            'after' => 'code',
          ]
        ),
        new Column(
          'enquiry',
          [
            'type' => Column::TYPE_CHAR,
            'default' => "N",
            'notNull' => true,
            'size' => 1,
            'after' => 'tag',
          ]
        ),
        new Column(
          'starttime',
          [
            'type' => Column::TYPE_DATETIME,
            'size' => 1,
            'after' => 'enquiry',
          ]
        ),
        new Column(
          'endtime',
          [
            'type' => Column::TYPE_DATETIME,
            'size' => 1,
            'after' => 'starttime',
          ]
        ),
        new Column(
          'finished',
          [
            'type' => Column::TYPE_DATETIME,
            'size' => 1,
            'after' => 'endtime',
          ]
        ),
      ],
      'indexes' => [
        new Index('PRIMARY', ['id'], 'PRIMARY'),
        new Index('exam_id', ['exam_id'], null),
        new Index('user', ['user'], null),
      ],
      'references' => [
        new Reference(
          'students_ibfk_1',
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
        'AUTO_INCREMENT' => '23',
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
