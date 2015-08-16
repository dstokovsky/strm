<?php

use Phinx\Migration\AbstractMigration;

class UserMigration extends AbstractMigration
{
    /**
     * Migrate Up.
     */
    public function up()
    {
        $table = $this->table('user');
        $table->addColumn('email', 'string', ['length' => 100, 'null' => false])
            ->addColumn('first_name', 'string', ['length' => 50, 'default' => '', 'null' => false])
            ->addColumn('second_name', 'string', ['length' => 50, 'default' => '', 'null' => false])
            ->addTimestamps()
            ->addIndex(['email'], ['unique' => true])
            ->save();
    }
    
    /**
     * Migrate Down.
     */
    public function down()
    {
        $this->table('user')->drop();
    }
}
