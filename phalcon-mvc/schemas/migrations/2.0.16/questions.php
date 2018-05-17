<?php

use Phalcon\Db\Column;
use Phalcon\Db\Index;
use Phalcon\Db\Reference;
use Phalcon\Mvc\Model\Migration;

/**
 * Class QuestionsMigration_216
 */
class QuestionsMigration_216 extends Migration {
  /**
   * Define the table structure
   *
   * @return void
   */
  public function morph() {
    $this->morphTable('questions', [
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
          'topic_id',
          [
            'type' => Column::TYPE_INTEGER,
            'notNull' => true,
            'size' => 11,
            'after' => 'exam_id',
          ]
        ),
        new Column(
          'slot',
          [
            'type' => Column::TYPE_INTEGER,
            'default' => "0",
            'size' => 11,
            'after' => 'topic_id',
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
          'user',
          [
            'type' => Column::TYPE_VARCHAR,
            'notNull' => true,
            'size' => 60,
            'after' => 'uuid',
          ]
        ),
        new Column(
          'score',
          [
            'type' => Column::TYPE_FLOAT,
            'notNull' => true,
            'size' => 1,
            'after' => 'user',
          ]
        ),
        new Column(
          'name',
          [
            'type' => Column::TYPE_VARCHAR,
            'notNull' => true,
            'size' => 30,
            'after' => 'score',
          ]
        ),
        new Column(
          'quest',
          [
            'type' => Column::TYPE_MEDIUMBLOB,
            'notNull' => true,
            'size' => 1,
            'after' => 'name',
          ]
        ),
        new Column(
          'answer',
          [
            'type' => Column::TYPE_BLOB,
            'size' => 1,
            'after' => 'quest',
          ]
        ),
        new Column(
          'status',
          [
            'type' => Column::TYPE_CHAR,
            'default' => "active",
            'notNull' => true,
            'size' => 1,
            'after' => 'answer',
          ]
        ),
        new Column(
          'comment',
          [
            'type' => Column::TYPE_TEXT,
            'size' => 1,
            'after' => 'status',
          ]
        ),
        new Column(
          'grades',
          [
            'type' => Column::TYPE_VARCHAR,
            'size' => 200,
            'after' => 'comment',
          ]
        ),
      ],
      'indexes' => [
        new Index('PRIMARY', ['id'], 'PRIMARY'),
        new Index('exam_id', ['exam_id'], null),
        new Index('questions_ibfk_2', ['topic_id'], null),
      ],
      'references' => [
        new Reference(
          'questions_ibfk_1',
          [
            'referencedTable' => 'exams',
            'referencedSchema' => 'openexam2prod',
            'columns' => ['exam_id'],
            'referencedColumns' => ['id'],
            'onUpdate' => 'RESTRICT',
            'onDelete' => 'CASCADE',
          ]
        ),
        new Reference(
          'questions_ibfk_2',
          [
            'referencedTable' => 'topics',
            'referencedSchema' => 'openexam2prod',
            'columns' => ['topic_id'],
            'referencedColumns' => ['id'],
            'onUpdate' => 'RESTRICT',
            'onDelete' => 'CASCADE',
          ]
        ),
      ],
      'options' => [
        'TABLE_TYPE' => 'BASE TABLE',
        'AUTO_INCREMENT' => '45513',
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
