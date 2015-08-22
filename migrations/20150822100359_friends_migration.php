<?php

use Phinx\Migration\AbstractMigration;

class FriendsMigration extends AbstractMigration
{
    /**
     * Migrate Up
     */
    public function up()
    {
        $table = $this->table('users_friends');
        $table->addColumn('user_id', 'integer', ['null' => false])
            ->addColumn('friend_id', 'integer', ['null' => false])
            ->addColumn('status', 'enum', ['values' => ['friend', 'subscriber']])
            ->addIndex(['user_id', 'friend_id'], ['unique' => true])
            ->addIndex(['user_id'])
            ->addIndex(['friend_id'])
            ->addIndex(['user_id', 'status'])
            ->addTimestamps()
            ->save();
    }
    
    /**
     * Migrate Down
     */
    public function down()
    {
        $this->table('users_friends')->drop();
    }
}
