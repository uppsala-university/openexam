<?php

use Phalcon\Db\Column;
use Phalcon\Db\Index;
use Phalcon\Mvc\Model\Migration;

/**
 * Class ExamsMigration_210
 */
class ExamsMigration_210 extends Migration {
  /**
   * Define the table structure
   *
   * @return void
   */
  public function morph() {
    $this->morphTable('exams', [
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
          'name',
          [
            'type' => Column::TYPE_VARCHAR,
            'notNull' => true,
            'size' => 200,
            'after' => 'id',
          ]
        ),
        new Column(
          'descr',
          [
            'type' => Column::TYPE_TEXT,
            'size' => 1,
            'after' => 'name',
          ]
        ),
        new Column(
          'starttime',
          [
            'type' => Column::TYPE_DATETIME,
            'size' => 1,
            'after' => 'descr',
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
          'created',
          [
            'type' => Column::TYPE_DATETIME,
            'notNull' => true,
            'size' => 1,
            'after' => 'endtime',
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
        new Column(
          'creator',
          [
            'type' => Column::TYPE_VARCHAR,
            'notNull' => true,
            'size' => 60,
            'after' => 'updated',
          ]
        ),
        new Column(
          'details',
          [
            'type' => Column::TYPE_INTEGER,
            'default' => "3",
            'notNull' => true,
            'size' => 11,
            'after' => 'creator',
          ]
        ),
        new Column(
          'enquiry',
          [
            'type' => Column::TYPE_CHAR,
            'default' => "N",
            'notNull' => true,
            'size' => 1,
            'after' => 'details',
          ]
        ),
        new Column(
          'decoded',
          [
            'type' => Column::TYPE_CHAR,
            'default' => "N",
            'notNull' => true,
            'size' => 1,
            'after' => 'enquiry',
          ]
        ),
        new Column(
          'published',
          [
            'type' => Column::TYPE_CHAR,
            'default' => "N",
            'size' => 1,
            'after' => 'decoded',
          ]
        ),
        new Column(
          'orgunit',
          [
            'type' => Column::TYPE_VARCHAR,
            'notNull' => true,
            'size' => 150,
            'after' => 'published',
          ]
        ),
        new Column(
          'orgdiv',
          [
            'type' => Column::TYPE_VARCHAR,
            'notNull' => true,
            'size' => 100,
            'after' => 'orgunit',
          ]
        ),
        new Column(
          'orgdep',
          [
            'type' => Column::TYPE_VARCHAR,
            'size' => 100,
            'after' => 'orgdiv',
          ]
        ),
        new Column(
          'orggrp',
          [
            'type' => Column::TYPE_VARCHAR,
            'size' => 100,
            'after' => 'orgdep',
          ]
        ),
        new Column(
          'grades',
          [
            'type' => Column::TYPE_VARCHAR,
            'notNull' => true,
            'size' => 200,
            'after' => 'orggrp',
          ]
        ),
        new Column(
          'course',
          [
            'type' => Column::TYPE_VARCHAR,
            'size' => 30,
            'after' => 'grades',
          ]
        ),
        new Column(
          'code',
          [
            'type' => Column::TYPE_VARCHAR,
            'size' => 20,
            'after' => 'course',
          ]
        ),
        new Column(
          'testcase',
          [
            'type' => Column::TYPE_CHAR,
            'default' => "N",
            'notNull' => true,
            'size' => 1,
            'after' => 'code',
          ]
        ),
        new Column(
          'lockdown',
          [
            'type' => Column::TYPE_VARCHAR,
            'default' => "{"enable":true}",
            'notNull' => true,
            'size' => 500,
            'after' => 'testcase',
          ]
        ),
      ],
      'indexes' => [
        new Index('PRIMARY', ['id'], 'PRIMARY'),
        new Index('creator', ['creator'], null),
        new Index('name', ['name'], null),
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
