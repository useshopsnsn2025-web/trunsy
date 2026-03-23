<?php

use think\migration\Migrator;
use think\migration\db\Column;

class AddPinnedToConversations extends Migrator
{
    /**
     * 给会话表添加置顶字段
     */
    public function change()
    {
        $table = $this->table('conversations');
        $table
            ->addColumn('is_pinned', 'integer', [
                'signed' => false,
                'null' => false,
                'default' => 0,
                'comment' => '是否置顶：0=否，1=是',
                'after' => 'is_closed'
            ])
            ->addColumn('pinned_at', 'datetime', [
                'null' => true,
                'default' => null,
                'comment' => '置顶时间',
                'after' => 'is_pinned'
            ])
            ->addIndex(['user_id', 'is_pinned'], ['name' => 'idx_user_pinned'])
            ->update();
    }
}
