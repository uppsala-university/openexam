<?php


use Phinx\Migration\AbstractMigration;

class CreateBaseline extends AbstractMigration {
  /**
   * Change Method.
   *
   * Write your reversible migrations using this method.
   *
   * More information on writing migrations is available here:
   * http://docs.phinx.org/en/latest/migrations.html#the-abstractmigration-class
   *
   * The following commands can be used in this method and Phinx will
   * automatically reverse them when rolling back:
   *
   *    createTable
   *    renameTable
   *    addColumn
   *    renameColumn
   *    addIndex
   *    addForeignKey
   *
   * Remember to call "create()" or "update()" and NOT "save()" when working
   * with the Table class.
   */
  public function change()
  {
    $tableNames = [
      'access', 'admin', 'answer', 'audit', 'computer', 'contributor', 'corrector', 'decoder', 'exam',
      'files', 'invigilator', 'lock', 'question', 'render', 'resource', 'result', 'room', 'session',
      'setting', 'student', 'superinvigilator', 'teacher', 'topic', 'user'
    ];
    foreach($tableNames as $tableName) {
      $table = $this->table($tableName);
      $table->addColumn('res', 'string', ['limit' => 20, 'default' => null, 'null' => true])
            ->addColumn('rid', 'integer', ['limit' => 11, 'default' => null, 'null' => true])
            ->addColumn('type', 'char', ['limit' => 6, 'default' => null, 'null' => true])
            ->addColumn('user', 'string', ['limit' => 60, 'default' => null, 'null' => true])
            ->addColumn('time', 'datetime', ['default' => null, 'null' => true])
            ->addColumn('changes', 'blob', ['limit' => 16777215, 'default' => null, 'null' => true])
            ->addIndex(['time'], ['name' => 'time'])
            ->addIndex(['user'], ['name' => 'user'])
            ->addIndex(['rid'], ['name' => 'rid'])
            ->addIndex(['res'], ['name' => 'res'])
            ->create();
    }
  }
}
