<?php
declare(strict_types=1);

namespace app\common\model;

use think\Model;

/**
 * 消息模型
 */
class Message extends Model
{
    protected $name = 'messages';
    protected $pk = 'id';
    protected $autoWriteTimestamp = 'datetime';
    protected $createTime = 'created_at';
    protected $updateTime = false;

    /**
     * JSON 字段
     * @var array
     */
    protected $json = ['extra'];

    // 消息类型
    const TYPE_TEXT = 1;    // 文本
    const TYPE_IMAGE = 2;   // 图片
    const TYPE_GOODS = 3;   // 商品卡片
    const TYPE_ORDER = 4;   // 订单卡片
    const TYPE_SYSTEM = 5;  // 系统消息
    const TYPE_RECALLED = 6; // 已撤回

    // 发送者类型
    const SENDER_USER = 'user';
    const SENDER_SERVICE = 'service';
    const SENDER_SYSTEM = 'system';

    // 撤回时间限制（秒）
    const RECALL_TIME_LIMIT = 120; // 2分钟内可撤回

    /**
     * 关联会话
     */
    public function conversation()
    {
        return $this->belongsTo(Conversation::class, 'conversation_id', 'id');
    }

    /**
     * 关联发送用户
     */
    public function senderUser()
    {
        return $this->belongsTo(User::class, 'sender_id', 'id')
            ->field('id, uuid, nickname, avatar');
    }

    /**
     * 关联发送客服
     */
    public function senderService()
    {
        return $this->belongsTo(CustomerService::class, 'sender_id', 'id');
    }

    /**
     * 创建消息
     */
    public static function createMessage(
        int $conversationId,
        int $senderId,
        string $senderType,
        string $content,
        int $msgType = self::TYPE_TEXT,
        ?array $extra = null,
        ?int $quoteId = null
    ): self {
        $message = new self();
        $message->conversation_id = $conversationId;
        $message->sender_id = $senderId;
        $message->sender_type = $senderType;
        $message->content = $content;
        $message->setAttr('type', $msgType);  // 使用 setAttr 避免与 $type 属性冲突
        $message->extra = $extra;
        $message->is_read = 0;
        $message->quote_id = $quoteId;
        $message->save();

        return $message;
    }

    /**
     * 检查是否可以撤回
     */
    public function canRecall(int $userId): bool
    {
        // 只能撤回自己发送的消息
        if ($this->sender_id !== $userId || $this->sender_type !== self::SENDER_USER) {
            return false;
        }

        // 已撤回的消息不能再次撤回
        if ($this->getData('type') == self::TYPE_RECALLED) {
            return false;
        }

        // 检查时间限制
        $createdAt = strtotime($this->created_at);
        $now = time();
        return ($now - $createdAt) <= self::RECALL_TIME_LIMIT;
    }

    /**
     * 撤回消息
     */
    public function recall(): bool
    {
        $this->setAttr('type', self::TYPE_RECALLED);
        $this->content = '';
        $this->extra = null;
        return $this->save();
    }

    /**
     * 关联引用的消息
     */
    public function quotedMessage()
    {
        return $this->belongsTo(self::class, 'quote_id', 'id');
    }
}
