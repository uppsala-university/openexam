<?php 

use Phalcon\Db\Column;
use Phalcon\Db\Index;
use Phalcon\Db\Reference;
use Phalcon\Mvc\Model\Migration;

/**
 * Class ResultsMigration_210
 */
class ResultsMigration_210 extends Migration
{
    /**
     * Define the table structure
     *
     * @return void
     */
    public function morph()
    {
        $this->morphTable('results', [
                'columns' => [
                    new Column(
                        'id',
                        [
                            'type' => Column::TYPE_INTEGER,
                            'notNull' => true,
                            'autoIncrement' => true,
                            'size' => 11,
                            'first' => true
                        ]
                    ),
                    new Column(
                        'answer_id',
                        [
                            'type' => Column::TYPE_INTEGER,
                            'notNull' => true,
                            'size' => 11,
                            'after' => 'id'
                        ]
                    ),
                    new Column(
                        'corrector_id',
                        [
                            'type' => Column::TYPE_INTEGER,
                            'notNull' => true,
                            'size' => 11,
                            'after' => 'answer_id'
                        ]
                    ),
                    new Column(
                        'question_id',
                        [
                            'type' => Column::TYPE_INTEGER,
                            'notNull' => true,
                            'size' => 11,
                            'after' => 'corrector_id'
                        ]
                    ),
                    new Column(
                        'correction',
                        [
                            'type' => Column::TYPE_CHAR,
                            'default' => "waiting",
                            'notNull' => true,
                            'size' => 1,
                            'after' => 'question_id'
                        ]
                    ),
                    new Column(
                        'score',
                        [
                            'type' => Column::TYPE_VARCHAR,
                            'notNull' => true,
                            'size' => 1024,
                            'after' => 'correction'
                        ]
                    ),
                    new Column(
                        'comment',
                        [
                            'type' => Column::TYPE_TEXT,
                            'size' => 1,
                            'after' => 'score'
                        ]
                    )
                ],
                'indexes' => [
                    new Index('PRIMARY', ['id'], 'PRIMARY'),
                    new Index('answer_id', ['answer_id'], null),
                    new Index('corrector_id', ['corrector_id'], null),
                    new Index('question_id', ['question_id'], null)
                ],
                'references' => [
                    new Reference(
                        'results_ibfk_1',
                        [
                            'referencedTable' => 'answers',
                            'referencedSchema' => 'openexam',
                            'columns' => ['answer_id'],
                            'referencedColumns' => ['id'],
                            'onUpdate' => 'RESTRICT',
                            'onDelete' => 'CASCADE'
                        ]
                    ),
                    new Reference(
                        'results_ibfk_2',
                        [
                            'referencedTable' => 'correctors',
                            'referencedSchema' => 'openexam',
                            'columns' => ['corrector_id'],
                            'referencedColumns' => ['id'],
                            'onUpdate' => 'RESTRICT',
                            'onDelete' => 'CASCADE'
                        ]
                    ),
                    new Reference(
                        'results_ibfk_3',
                        [
                            'referencedTable' => 'questions',
                            'referencedSchema' => 'openexam',
                            'columns' => ['question_id'],
                            'referencedColumns' => ['id'],
                            'onUpdate' => 'RESTRICT',
                            'onDelete' => 'CASCADE'
                        ]
                    )
                ],
                'options' => [
                    'TABLE_TYPE' => 'BASE TABLE',
                    'AUTO_INCREMENT' => '1',
                    'ENGINE' => 'InnoDB',
                    'TABLE_COLLATION' => 'utf8_general_ci'
                ],
            ]
        );
    }

    /**
     * Run the migrations
     *
     * @return void
     */
    public function up()
    {

    }

    /**
     * Reverse the migrations
     *
     * @return void
     */
    public function down()
    {

    }

}
