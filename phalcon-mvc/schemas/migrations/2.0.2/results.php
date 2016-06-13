<?php 

use Phalcon\Db\Column;
use Phalcon\Db\Index;
use Phalcon\Db\Reference;
use Phalcon\Mvc\Model\Migration;

class ResultsMigration_202 extends Migration
{

    public function up()
    {
        $this->morphTable(
            'results',
            array(
            'columns' => array(
                new Column(
                    'id',
                    array(
                        'type' => Column::TYPE_INTEGER,
                        'notNull' => true,
                        'autoIncrement' => true,
                        'size' => 11,
                        'first' => true
                    )
                ),
                new Column(
                    'answer_id',
                    array(
                        'type' => Column::TYPE_INTEGER,
                        'notNull' => true,
                        'size' => 11,
                        'after' => 'id'
                    )
                ),
                new Column(
                    'score',
                    array(
                        'type' => Column::TYPE_FLOAT,
                        'notNull' => true,
                        'size' => 1,
                        'after' => 'answer_id'
                    )
                ),
                new Column(
                    'comment',
                    array(
                        'type' => Column::TYPE_TEXT,
                        'size' => 1,
                        'after' => 'score'
                    )
                )
            ),
            'indexes' => array(
                new Index('PRIMARY', array('id')),
                new Index('answer_id', array('answer_id'))
            ),
            'references' => array(
                new Reference('results_ibfk_1', array(
                    'referencedSchema' => 'openexam',
                    'referencedTable' => 'answers',
                    'columns' => array('answer_id'),
                    'referencedColumns' => array('id')
                ))
            ),
            'options' => array(
                'TABLE_TYPE' => 'BASE TABLE',
                'AUTO_INCREMENT' => '35',
                'ENGINE' => 'InnoDB',
                'TABLE_COLLATION' => 'utf8_general_ci'
            )
        )
        );
    }
}
