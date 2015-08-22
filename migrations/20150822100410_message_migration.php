<?php

use Phinx\Migration\AbstractMigration;

class MessageMigration extends AbstractMigration
{
    /**
     * Migrate Up
     */
    public function up()
    {
        $table = $this->table('message');
        $table->addColumn('author_id', 'integer', ['null' => false])
            ->addColumn('recipient_id', 'integer', ['null' => false])
            ->addColumn('text', 'string', ['length' => 200, 'null' => false, 'default' => ''])
            ->addIndex(['author_id', 'recipient_id'])
            ->addIndex(['author_id'])
            ->addIndex(['recipient_id'])
            ->addTimestamps()
            ->save();
    }
    
    /**
     * Migrate Down
     */
    public function down()        
    {
        $this->table('message')->drop();
    }
}
