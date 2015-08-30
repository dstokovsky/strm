<?php

use Phinx\Migration\AbstractMigration;

class UserNewFieldsMigration extends AbstractMigration
{
    /**
     * Change Method.
     *
     * Write your reversible migrations using this method.
     *
     * More information on writing migrations is available here:
     * http://docs.phinx.org/en/latest/migrations.html#the-abstractmigration-class
     */
    public function change()
    {
        $table = $this->table('user');
        $table->addColumn('password', 'string', ['length' => 200, 'null' => false, 'default' => '', 'after' => 'email'])
            ->addColumn('account', 'enum', ['null' => false, 'values' => ['email', 'facebook', 'linkedin'], 'after' => 'password'])
            ->save();
    }
}
