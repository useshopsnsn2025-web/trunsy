<?php

use think\migration\Migrator;
use think\migration\db\Column;

class CreateUserBlocksTable extends Migrator
{
    /**
     * 创建用户拉黑表
     */
    public function change()
    {
        $table = $this->table('user_blocks', ['engine' => 'InnoDB', 'collation' => 'utf8mb4_unicode_ci']);
        $table
            ->addColumn('user_id', 'integer', ['signed' => false, 'null' => false, 'comment' => '用户ID'])
            ->addColumn('blocked_user_id', 'integer', ['signed' => false, 'null' => false, 'comment' => '被拉黑用户ID'])
            ->addColumn('created_at', 'datetime', ['null' => true, 'comment' => '创建时间'])
            ->addIndex(['user_id', 'blocked_user_id'], ['unique' => true, 'name' => 'idx_user_blocked'])
            ->addIndex(['user_id'], ['name' => 'idx_user_id'])
            ->addIndex(['blocked_user_id'], ['name' => 'idx_blocked_user_id'])
            ->create();
    }
}
