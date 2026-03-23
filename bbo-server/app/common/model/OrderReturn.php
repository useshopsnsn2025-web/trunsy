<?php
declare(strict_types=1);

namespace app\common\model;

use think\Model;

/**
 * 订单退货申请模型
 */
class OrderReturn extends Model
{
    protected $name = 'order_returns';
    protected $pk = 'id';
    protected $autoWriteTimestamp = 'datetime';
    protected $createTime = 'created_at';
    protected $updateTime = 'updated_at';

    protected $type = [
        'id' => 'integer',
        'order_id' => 'integer',
        'buyer_id' => 'integer',
        'seller_id' => 'integer',
        'goods_id' => 'integer',
        'goods_snapshot' => 'json',
        'images' => 'json',
        'type' => 'integer',
        'status' => 'integer',
        'original_order_status' => 'integer',
        'refund_amount' => 'float',
    ];

    /**
     * 退货类型
     */
    const TYPE_REFUND_ONLY = 1;    // 仅退款
    const TYPE_RETURN_REFUND = 2;  // 退货退款

    /**
     * 退货状态
     */
    const STATUS_PENDING = 0;      // 待处理
    const STATUS_APPROVED = 1;     // 已同意
    const STATUS_REJECTED = 2;     // 已拒绝
    const STATUS_CANCELLED = 3;    // 已取消
    const STATUS_IN_RETURN = 4;    // 退货中（买家已发货）
    const STATUS_COMPLETED = 5;    // 已完成

    /**
     * 退货原因代码
     */
    const REASONS = [
        'not_as_described' => 'Item not as described',
        'wrong_item' => 'Wrong item received',
        'damaged' => 'Item damaged/defective',
        'missing_parts' => 'Missing parts/accessories',
        'quality_issue' => 'Quality issue',
        'not_needed' => 'No longer needed',
        'better_price' => 'Found better price',
        'other' => 'Other',
    ];

    /**
     * 生成退货单号
     */
    public static function generateReturnNo(): string
    {
        return 'RT' . date('YmdHis') . sprintf('%04d', mt_rand(0, 9999));
    }

    /**
     * 关联订单
     */
    public function order()
    {
        return $this->belongsTo(Order::class, 'order_id', 'id');
    }

    /**
     * 关联买家
     */
    public function buyer()
    {
        return $this->belongsTo(User::class, 'buyer_id', 'id')
            ->field('id,uuid,nickname,avatar');
    }

    /**
     * 关联卖家
     */
    public function seller()
    {
        return $this->belongsTo(User::class, 'seller_id', 'id')
            ->field('id,uuid,nickname,avatar');
    }

    /**
     * 检查订单是否可以申请退货
     */
    public static function canRequestReturn(Order $order): array
    {
        // 只有已发货和已完成的订单可以申请退货
        $allowedStatuses = [
            Order::STATUS_PENDING_RECEIPT,  // 待收货
            Order::STATUS_COMPLETED,        // 已完成
        ];

        if (!in_array($order->status, $allowedStatuses)) {
            return ['can' => false, 'reason' => 'Order status does not allow return request'];
        }

        // 检查是否已有进行中的退货申请
        $existingReturn = self::where('order_id', $order->id)
            ->whereIn('status', [self::STATUS_PENDING, self::STATUS_APPROVED, self::STATUS_IN_RETURN])
            ->find();

        if ($existingReturn) {
            return ['can' => false, 'reason' => 'Return request already exists', 'return_id' => $existingReturn->id];
        }

        // 已完成订单：检查是否在退货期限内（14天）
        if ($order->status === Order::STATUS_COMPLETED) {
            $completedAt = strtotime($order->completed_at);
            $returnDeadline = $completedAt + (14 * 24 * 60 * 60); // 14天
            if (time() > $returnDeadline) {
                return ['can' => false, 'reason' => 'Return period has expired'];
            }
        }

        return ['can' => true];
    }

    /**
     * 格式化为API数组
     */
    public function toApiArray(string $locale = 'en-us'): array
    {
        $translations = UiTranslation::getTranslationsByNamespace($locale, 'return');

        // 使用 getData() 获取原始字段值，避免与 Model 基类的 $type 属性冲突
        $typeValue = $this->getData('type');
        $statusValue = $this->getData('status');

        return [
            'id' => $this->id,
            'return_no' => $this->return_no,
            'order_id' => $this->order_id,
            'order_no' => $this->order_no,
            'type' => $typeValue,
            'type_text' => $typeValue == self::TYPE_REFUND_ONLY
                ? ($translations['typeRefundOnly'] ?? 'Refund Only')
                : ($translations['typeReturnRefund'] ?? 'Return & Refund'),
            'reason' => $this->reason,
            'reason_text' => $translations['reasons'][$this->reason] ?? $this->reason,
            'reason_detail' => $this->reason_detail,
            'images' => $this->images ?? [],
            'refund_amount' => $this->refund_amount,
            'currency' => $this->currency,
            'status' => $statusValue,
            'status_text' => $translations['status'][$statusValue] ?? '',
            'reject_reason' => $this->reject_reason,
            'seller_reply' => $this->seller_reply,
            'seller_replied_at' => $this->seller_replied_at,
            'return_tracking_no' => $this->return_tracking_no,
            'return_carrier' => $this->return_carrier,
            'shipped_at' => $this->shipped_at,
            'received_at' => $this->received_at,
            'refunded_at' => $this->refunded_at,
            'expired_at' => $this->expired_at,
            'created_at' => $this->created_at,
            'goods_snapshot' => $this->goods_snapshot,
        ];
    }
}
