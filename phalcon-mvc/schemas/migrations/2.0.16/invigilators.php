<?php

use Phalcon\Db\Column;
use Phalcon\Db\Index;
use Phalcon\Db\Reference;
use Phalcon\Mvc\Model\Migration;

/**
 * Class InvigilatorsMigration_216
 */
class InvigilatorsMigration_216 extends Migration {
  /**
   * Define the table structure
   *
   * @return void
   */
  public function morph() {
    $this->morphTable('invigilators', [
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
      ],
      'indexes' => [
        new Index('PRIMARY', ['id'], 'PRIMARY'),
        new Index('exam_id', ['exam_id'], null),
        new Index('user', ['user'], null),
      ],
      'references' => [
        new Reference(
          'invigilators_ibfk_1',
          [
            'referencedTable' => 'exams',
            'referencedSchema' => 'openexam2prod',
            'columns' => ['exam_id'],
            'referencedColumns' => ['id'],
            'onUpdate' => 'RESTRICT',
            'onDelete' => 'CASCADE',
          ]
        ),
      ],
      'options' => [
        'TABLE_TYPE' => 'BASE TABLE',
        'AUTO_INCREMENT' => '34035',
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
