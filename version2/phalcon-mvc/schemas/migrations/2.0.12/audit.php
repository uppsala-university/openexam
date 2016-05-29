<?php 

use Phalcon\Db\Column;
use Phalcon\Db\Index;
use Phalcon\Db\Reference;
use Phalcon\Mvc\Model\Migration;

class AuditMigration_2012 extends Migration
{

    public function up()
    {
        $this->morphTable(
            'audit',
            array(
            'columns' => array(
                new Column(
                    'id',
                    array(
                        'type' => Column::TYPE_INTEGER,
                        'size' => 11,
                        'first' => true
                    )
                ),
                new Column(
                    'model',
                    array(
                        'type' => Column::TYPE_VARCHAR,
                        'size' => 20,
                        'after' => 'id'
                    )
                ),
                new Column(
                    'type',
                    array(
                        'type' => Column::TYPE_CHAR,
                        'size' => 6,
                        'after' => 'model'
                    )
                ),
                new Column(
                    'user',
                    array(
                        'type' => Column::TYPE_VARCHAR,
                        'size' => 60,
                        'after' => 'type'
                    )
                ),
                new Column(
                    'time',
                    array(
                        'type' => Column::TYPE_DATETIME,
                        'size' => 1,
                        'after' => 'user'
                    )
                ),
                new Column(
                    'changes',
                    array(
                        'type' => Column::TYPE_VARCHAR,
                        'size' => 1,
                        'after' => 'time'
                    )
                )
            ),
            'options' => array(
                'TABLE_TYPE' => 'BASE TABLE',
                'AUTO_INCREMENT' => '',
                'ENGINE' => 'InnoDB',
                'TABLE_COLLATION' => 'utf8_general_ci'
            )
        )
        );
    }
}
