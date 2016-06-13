<?php 

use Phalcon\Db\Column;
use Phalcon\Db\Index;
use Phalcon\Db\Reference;
use Phalcon\Mvc\Model\Migration;

class ResultsMigration_206 extends Migration
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
                    'corrector_id',
                    array(
                        'type' => Column::TYPE_INTEGER,
                        'notNull' => true,
                        'size' => 11,
                        'after' => 'answer_id'
                    )
                ),
                new Column(
                    'correction',
                    array(
                        'type' => Column::TYPE_CHAR,
                        'notNull' => true,
                        'size' => 1,
                        'after' => 'corrector_id'
                    )
                ),
                new Column(
                    'score',
                    array(
                        'type' => Column::TYPE_VARCHAR,
                        'notNull' => true,
                        'size' => 255,
                        'after' => 'correction'
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
                new Index('answer_id', array('answer_id')),
                new Index('corrector_id', array('corrector_id'))
            ),
            'references' => array(
                new Reference('results_ibfk_1', array(
                    'referencedSchema' => 'openexam',
                    'referencedTable' => 'answers',
                    'columns' => array('answer_id'),
                    'referencedColumns' => array('id')
                )),
                new Reference('results_ibfk_2', array(
                    'referencedSchema' => 'openexam',
                    'referencedTable' => 'correctors',
                    'columns' => array('corrector_id'),
                    'referencedColumns' => array('id')
                ))
            ),
            'options' => array(
                'TABLE_TYPE' => 'BASE TABLE',
                'AUTO_INCREMENT' => '1171',
                'ENGINE' => 'InnoDB',
                'TABLE_COLLATION' => 'utf8_general_ci'
            )
        )
        );
    }
}
