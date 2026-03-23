<?php

use think\migration\Migrator;
use think\migration\db\Column;

class AddQuoteIdToMessages extends Migrator
{
    /**
     * 给消息表添加引用消息ID字段
     */
    public function change()
    {
        $table = $this->table('messages');
        $table
            ->addColumn('quote_id', 'integer', [
                'signed' => false,
                'null' => true,
                'default' => null,
                'comment' => '引用的消息ID',
                'after' => 'extra'
            ])
            ->addIndex(['quote_id'], ['name' => 'idx_quote_id'])
            ->update();
    }
}
