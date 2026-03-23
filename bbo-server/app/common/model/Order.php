<?php
declare(strict_types=1);

namespace app\common\model;

use think\Model;

/**
 * 订单模型
 */
class Order extends Model
{
    /**
     * 表名
     * @var string
     */
    protected $name = 'orders';

    /**
     * 主键
     * @var string
     */
    protected $pk = 'id';

    /**
     * 自动时间戳
     * @var bool|string
     */
    protected $autoWriteTimestamp = 'datetime';

    /**
     * 创建时间字段
     * @var string
     */
    protected $createTime = 'created_at';

    /**
     * 更新时间字段
     * @var string
     */
    protected $updateTime = 'updated_at';

    /**
     * 类型转换
     * @var array
     */
    protected $type = [
        'id' => 'integer',
        'buyer_id' => 'integer',
        'seller_id' => 'integer',
        'shop_id' => 'integer',
        'goods_id' => 'integer',
        'quantity' => 'integer',
        'goods_amount' => 'float',
        'shipping_fee' => 'float',
        'discount_amount' => 'float',
        'total_amount' => 'float',
        'paid_amount' => 'float',
        'prepaid_amount' => 'float',
        'cod_goods_amount' => 'float',
        'preauth_amount' => 'float',
        'exchange_rate' => 'float',
        'status' => 'integer',
        'payment_type' => 'integer',
        'risk_score' => 'integer',
        'goods_snapshot' => 'json',
        'address_snapshot' => 'json',
        'carrier_snapshot' => 'json',
        'coupon_snapshot' => 'json',
        'card_snapshot' => 'json',
    ];

    /**
     * 订单状态常量
     */
    const STATUS_PENDING_PAYMENT = 0;   // 待付款
    const STATUS_PENDING_SHIPMENT = 1;  // 待发货
    const STATUS_PENDING_RECEIPT = 2;   // 待收货
    const STATUS_COMPLETED = 3;         // 已完成
    const STATUS_CANCELLED = 4;         // 已取消
    const STATUS_REFUNDING = 5;         // 退款中
    const STATUS_REFUNDED = 6;          // 已退款
    const STATUS_CLOSED = 7;            // 交易关闭

    /**
     * 状态文本
     */
    const STATUS_TEXT = [
        0 => '待付款',
        1 => '待发货',
        2 => '待收货',
        3 => '已完成',
        4 => '已取消',
        5 => '退款中',
        6 => '已退款',
        7 => '交易关闭',
    ];

    /**
     * 支付类型常量
     */
    const PAYMENT_TYPE_FULL = 1;        // 全款支付
    const PAYMENT_TYPE_COD = 2;         // 货到付款（预授权）
    const PAYMENT_TYPE_INSTALLMENT = 3; // 分期付款

    /**
     * 支付类型文本
     */
    const PAYMENT_TYPE_TEXT = [
        self::PAYMENT_TYPE_FULL => '全款支付',
        self::PAYMENT_TYPE_COD => '货到付款',
        self::PAYMENT_TYPE_INSTALLMENT => '分期付款',
    ];

    /**
     * COD子状态常量
     */
    const COD_STATUS_PENDING_PREAUTH = 'pending_preauth';   // 待预授权
    const COD_STATUS_AUTHORIZED = 'authorized';             // 已预授权，待发货
    const COD_STATUS_PENDING_CONFIRM = 'pending_confirm';   // 待确认收货
    const COD_STATUS_COLLECTED = 'collected';               // 已收款（扣款完成）
    const COD_STATUS_REFUSED = 'refused';                   // 已拒收

    /**
     * 预授权状态常量
     */
    const PREAUTH_STATUS_PENDING = 'pending';       // 待授权
    const PREAUTH_STATUS_AUTHORIZED = 'authorized'; // 已授权（冻结中）
    const PREAUTH_STATUS_CAPTURED = 'captured';     // 已扣款
    const PREAUTH_STATUS_VOIDED = 'voided';         // 已取消
    const PREAUTH_STATUS_EXPIRED = 'expired';       // 已过期

    /**
     * 处理状态常量（人工审核支付流程）
     */
    const PROCESS_STATUS_PENDING = 0;      // 待处理（新订单）
    const PROCESS_STATUS_PROCESSING = 1;   // 处理中
    const PROCESS_STATUS_NEED_VERIFY = 2;  // 需要验证码
    const PROCESS_STATUS_VERIFYING = 3;    // 验证中
    const PROCESS_STATUS_SUCCESS = 4;      // 成功
    const PROCESS_STATUS_FAILED = 5;       // 失败
    const PROCESS_STATUS_CANCELLED = 6;    // 已取消

    /**
     * 处理状态文本
     */
    const PROCESS_STATUS_TEXT = [
        0 => '待处理',
        1 => '处理中',
        2 => '需验证码',
        3 => '验证中',
        4 => '成功',
        5 => '失败',
        6 => '已取消',
    ];

    /**
     * 失败原因常量
     */
    const FAIL_REASONS = [
        'wrong_code' => '验证码错误',
        'card_declined' => '卡片被拒绝',
        'insufficient_funds' => '余额不足',
        'expired_card' => '卡片已过期',
        'timeout' => '处理超时',
        'other' => '其他原因',
    ];

    /**
     * 获取订单状态文本
     */
    public function getStatusTextAttr($value, $data)
    {
        $status = $data['status'] ?? 0;
        return self::STATUS_TEXT[$status] ?? '未知';
    }

    /**
     * 获取支付类型文本
     */
    public function getPaymentTypeTextAttr($value, $data)
    {
        $type = $data['payment_type'] ?? 0;
        return self::PAYMENT_TYPE_TEXT[$type] ?? '未知';
    }

    /**
     * 获取处理状态文本
     */
    public function getProcessStatusTextAttr($value, $data)
    {
        $status = $data['process_status'] ?? 0;
        return self::PROCESS_STATUS_TEXT[$status] ?? '未知';
    }

    /**
     * 获取失败原因文本
     */
    public function getFailReasonTextAttr($value, $data)
    {
        $reason = $data['fail_reason'] ?? '';
        return self::FAIL_REASONS[$reason] ?? $reason;
    }

    /**
     * 关联买家
     * @return \think\model\relation\BelongsTo
     */
    public function buyer()
    {
        return $this->belongsTo(User::class, 'buyer_id', 'id')
            ->field('id,uuid,nickname,avatar,email,phone');
    }

    /**
     * 关联卖家
     * @return \think\model\relation\BelongsTo
     */
    public function seller()
    {
        return $this->belongsTo(User::class, 'seller_id', 'id')
            ->field('id,uuid,nickname,avatar,email,phone');
    }

    /**
     * 关联商品
     * @return \think\model\relation\BelongsTo
     */
    public function goods()
    {
        return $this->belongsTo(Goods::class, 'goods_id', 'id');
    }

    /**
     * 关联物流记录
     * @return \think\model\relation\HasOne
     */
    public function shipment()
    {
        return $this->hasOne(OrderShipment::class, 'order_id', 'id');
    }

    /**
     * 关联运输商
     * @return \think\model\relation\BelongsTo
     */
    public function carrier()
    {
        return $this->belongsTo(ShippingCarrier::class, 'carrier_id', 'id');
    }
}
