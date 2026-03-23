<?php
declare(strict_types=1);

namespace app\common\model;

use think\Model;

/**
 * 工单回复模型
 */
class TicketReply extends Model
{
    protected $name = 'ticket_replies';
    protected $pk = 'id';
    protected $autoWriteTimestamp = 'datetime';
    protected $createTime = 'created_at';
    protected $updateTime = false;

    protected $type = [
        'id' => 'integer',
        'ticket_id' => 'integer',
        'from_type' => 'integer',
        'from_id' => 'integer',
        'is_internal' => 'integer',
        'images' => 'json',
    ];

    // 发送方类型
    const FROM_USER = 1;
    const FROM_SERVICE = 2;
    const FROM_SYSTEM = 3;

    /**
     * 关联工单
     */
    public function ticket()
    {
        return $this->belongsTo(SupportTicket::class, 'ticket_id', 'id');
    }
}
