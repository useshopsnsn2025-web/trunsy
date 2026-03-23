<?php
declare(strict_types=1);

namespace app\common\model;

use think\Model;

/**
 * 会话模型
 */
class Conversation extends Model
{
    protected $name = 'conversations';
    protected $pk = 'id';
    protected $autoWriteTimestamp = 'datetime';
    protected $createTime = 'created_at';
    protected $updateTime = 'updated_at';

    // 注意：不使用 $type 属性，因为会与数据库字段 type 冲突

    /**
     * 获取会话类型
     * @return int
     */
    public function getConvType(): int
    {
        return (int) $this->getData('type');
    }

    // 会话类型
    const TYPE_PRIVATE = 1;  // 私聊
    const TYPE_SERVICE = 2;  // 客服

    // 会话场景
    const SCENE_PRE_SALE = 1;   // 售前咨询
    const SCENE_AFTER_SALE = 2; // 售后服务

    // 目标类型
    const TARGET_USER = 'user';
    const TARGET_SERVICE = 'service';

    // 商品所有者类型
    const OWNER_PLATFORM = 'platform';  // 平台自营
    const OWNER_SELLER = 'seller';      // 第三方卖家

    /**
     * 关联用户
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id')
            ->field('id, uuid, nickname, avatar');
    }

    /**
     * 关联目标用户
     */
    public function targetUser()
    {
        return $this->belongsTo(User::class, 'target_id', 'id')
            ->field('id, uuid, nickname, avatar');
    }

    /**
     * 关联客服
     */
    public function targetService()
    {
        return $this->belongsTo(CustomerService::class, 'target_id', 'id');
    }

    /**
     * 关联商品
     */
    public function goods()
    {
        return $this->belongsTo(Goods::class, 'goods_id', 'id')
            ->field('id, title, price, currency');
    }

    /**
     * 关联消息
     */
    public function messages()
    {
        return $this->hasMany(Message::class, 'conversation_id', 'id');
    }

    /**
     * 关联订单
     */
    public function order()
    {
        return $this->belongsTo(Order::class, 'order_id', 'id');
    }

    /**
     * 获取或创建会话
     * @param int $userId 用户ID
     * @param int $convType 会话类型
     * @param int $targetId 目标ID
     * @param string $targetType 目标类型
     * @param int|null $goodsId 商品ID
     * @param int $scene 场景（售前/售后）
     * @param string|null $goodsOwnerType 商品所有者类型
     * @param int|null $goodsOwnerId 商品所有者ID
     * @param int|null $orderId 订单ID（售后时）
     */
    public static function getOrCreate(
        int $userId,
        int $convType,
        int $targetId,
        string $targetType,
        ?int $goodsId = null,
        int $scene = self::SCENE_PRE_SALE,
        ?string $goodsOwnerType = null,
        ?int $goodsOwnerId = null,
        ?int $orderId = null
    ): self {
        // 查找现有会话
        $query = self::where('type', $convType)
            ->where('user_id', $userId)
            ->where('target_id', $targetId)
            ->where('target_type', $targetType)
            ->where('scene', $scene);

        // 售后会话需要匹配订单
        if ($scene === self::SCENE_AFTER_SALE && $orderId) {
            $query->where('order_id', $orderId);
        }

        // 客服会话区分：纯客服会话(goods_owner_id=0) 和 官方小号商品咨询(goods_owner_id>0) 独立
        if ($convType === self::TYPE_SERVICE) {
            $query->where('goods_owner_id', $goodsOwnerId ?? 0);
        } elseif ($goodsOwnerId && $goodsOwnerId > 0) {
            $query->where('goods_owner_id', $goodsOwnerId);
        }

        $conversation = $query->find();

        if (!$conversation) {
            $conversation = new self();
            $conversation->setAttr('type', $convType);
            $conversation->user_id = $userId;
            $conversation->target_id = $targetId;
            $conversation->target_type = $targetType;
            $conversation->goods_id = $goodsId;
            $conversation->scene = $scene;
            $conversation->order_id = $orderId;
            $conversation->goods_owner_type = $goodsOwnerType ?? self::OWNER_SELLER;
            $conversation->goods_owner_id = $goodsOwnerId ?? 0;
            $conversation->save();
        } elseif ($goodsId && $conversation->goods_id !== $goodsId) {
            // 同一个官方小号的会话，更新为最新咨询的商品
            $conversation->goods_id = $goodsId;
            $conversation->save();
        }

        return $conversation;
    }

    /**
     * 更新最后消息
     */
    public function updateLastMessage(string $content, string $senderType): void
    {
        $this->last_message = mb_substr($content, 0, 100);
        $this->last_message_time = date('Y-m-d H:i:s');

        // 更新未读数
        if ($senderType === 'user') {
            $this->target_unread = $this->target_unread + 1;
        } else {
            $this->user_unread = $this->user_unread + 1;
        }

        $this->save();
    }

    /**
     * 标记已读
     */
    public function markAsRead(string $readerType): void
    {
        if ($readerType === 'user') {
            $this->user_unread = 0;
        } else {
            $this->target_unread = 0;
        }
        $this->save();
    }
}
