<?php
declare(strict_types=1);

namespace app\common\model;

use think\Model;

/**
 * 快捷回复分组模型
 */
class QuickReplyGroup extends Model
{
    protected $name = 'quick_reply_groups';
    protected $pk = 'id';
    protected $autoWriteTimestamp = 'datetime';
    protected $createTime = 'created_at';
    protected $updateTime = 'updated_at';

    protected $type = [
        'id' => 'integer',
        'sort' => 'integer',
    ];

    /**
     * 关联快捷回复
     */
    public function replies()
    {
        return $this->hasMany(QuickReply::class, 'group_id', 'id');
    }
}
