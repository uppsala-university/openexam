<?php 

use Phalcon\Db\Column;
use Phalcon\Db\Index;
use Phalcon\Db\Reference;
use Phalcon\Mvc\Model\Migration;

/**
 * Class ResultsMigration_2013
 */
class ResultsMigration_2013 extends Migration
{
    /**
     * Define the table structure
     *
     * @return void
     */
    public function morph()
    {
        $this->morphTable('results', array(
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
                            'default' => "waiting",
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
                            'size' => 1024,
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
                    new Index('PRIMARY', array('id'), 'PRIMARY'),
                    new Index('answer_id', array('answer_id'), null),
                    new Index('corrector_id', array('corrector_id'), null)
                ),
                'references' => array(
                    new Reference(
                        'results_ibfk_1',
                        array(
                            'referencedSchema' => 'openexam2prod',
                            'referencedTable' => 'answers',
                            'columns' => array('answer_id','answer_id','answer_id'),
                            'referencedColumns' => array('id','id','id'),
                            'onUpdate' => 'RESTRICT',
                            'onDelete' => 'RESTRICT'
                        )
                    ),
                    new Reference(
                        'results_ibfk_2',
                        array(
                            'referencedSchema' => 'openexam2prod',
                            'referencedTable' => 'correctors',
                            'columns' => array('corrector_id','corrector_id'),
                            'referencedColumns' => array('id','id'),
                            'onUpdate' => 'RESTRICT',
                            'onDelete' => 'CASCADE'
                        )
                    )
                ),
                'options' => array(
                    'TABLE_TYPE' => 'BASE TABLE',
                    'AUTO_INCREMENT' => '96678',
                    'ENGINE' => 'InnoDB',
                    'TABLE_COLLATION' => 'utf8_general_ci'
                ),
            )
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
