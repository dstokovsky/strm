<?php

use Phinx\Migration\AbstractMigration;

class BlackListMigration extends AbstractMigration
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
        $table = $this->table('blacklist');
        $table->addColumn('user_id', 'integer', ['null' =>false])
            ->addColumn('banned_user_id', 'integer', ['null' => false])
            ->addIndex(['user_id'])
            ->addIndex(['user_id', 'banned_user_id'])
            ->addTimestamps()
            ->save();
    }
}
