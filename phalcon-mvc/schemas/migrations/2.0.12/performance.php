<?php 

use Phalcon\Db\Column;
use Phalcon\Db\Index;
use Phalcon\Db\Reference;
use Phalcon\Mvc\Model\Migration;

class PerformanceMigration_2012 extends Migration
{

    public function up()
    {
        $this->morphTable(
            'performance',
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
                    'mode',
                    array(
                        'type' => Column::TYPE_CHAR,
                        'notNull' => true,
                        'size' => 1,
                        'after' => 'id'
                    )
                ),
                new Column(
                    'source',
                    array(
                        'type' => Column::TYPE_VARCHAR,
                        'size' => 15,
                        'after' => 'mode'
                    )
                ),
                new Column(
                    'time',
                    array(
                        'type' => Column::TYPE_DATE,
                        'notNull' => true,
                        'size' => 1,
                        'after' => 'source'
                    )
                ),
                new Column(
                    'host',
                    array(
                        'type' => Column::TYPE_VARCHAR,
                        'notNull' => true,
                        'size' => 60,
                        'after' => 'time'
                    )
                ),
                new Column(
                    'addr',
                    array(
                        'type' => Column::TYPE_VARCHAR,
                        'notNull' => true,
                        'size' => 60,
                        'after' => 'host'
                    )
                ),
                new Column(
                    'milestone',
                    array(
                        'type' => Column::TYPE_CHAR,
                        'size' => 1,
                        'after' => 'addr'
                    )
                ),
                new Column(
                    'data',
                    array(
                        'type' => Column::TYPE_VARCHAR,
                        'notNull' => true,
                        'size' => 1024,
                        'after' => 'milestone'
                    )
                )
            ),
            'indexes' => array(
                new Index('PRIMARY', array('id'))
            ),
            'options' => array(
                'TABLE_TYPE' => 'BASE TABLE',
                'AUTO_INCREMENT' => '13350',
                'ENGINE' => 'InnoDB',
                'TABLE_COLLATION' => 'utf8_general_ci'
            )
        )
        );
    }
}
