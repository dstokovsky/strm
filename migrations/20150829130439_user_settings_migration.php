<?php

use Phinx\Migration\AbstractMigration;

class UserSettingsMigration extends AbstractMigration
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
        $table = $this->table('user_settings');
        $table->addColumn('user_id', 'integer', ['null' => false])
            ->addColumn('name', 'string', ['null' => false])
            ->addColumn('value', 'string', ['null' => false, 'default' => ''])
            ->addIndex(['user_id'])
            ->addIndex(['user_id', 'name'])
            ->addTimestamps()
            ->save();
    }
}
