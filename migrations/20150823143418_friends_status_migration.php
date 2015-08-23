<?php

use Phinx\Migration\AbstractMigration;

class FriendsStatusMigration extends AbstractMigration
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
        $this->table('users_friends')->removeIndex(['user_id', 'status'])->removeColumn('status');
    }
}
