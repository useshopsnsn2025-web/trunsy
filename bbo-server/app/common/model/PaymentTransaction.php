<?php
declare(strict_types=1);

namespace app\common\model;

use think\Model;

/**
 * 支付交易记录模型
 */
class PaymentTransaction extends Model
{
    /**
     * 表名
     * @var string
     */
    protected $name = 'payment_transactions';

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
        'order_id' => 'integer',
        'user_id' => 'integer',
        'amount' => 'float',
        'status' => 'integer',
        'gateway_response' => 'json',
    ];

    /**
     * 交易类型常量
     */
    const TYPE_PREAUTH = 'preauth';                  // 预授权（冻结）
    const TYPE_PREAUTH_CAPTURE = 'preauth_capture';  // 预授权完成扣款
    const TYPE_PREAUTH_VOID = 'preauth_void';        // 预授权取消释放
    const TYPE_FULL_PAY = 'full_pay';                // 全款支付
    const TYPE_REFUND = 'refund';                    // 退款

    /**
     * 交易状态常量
     */
    const STATUS_PROCESSING = 0;  // 处理中
    const STATUS_SUCCESS = 1;     // 成功
    const STATUS_FAILED = 2;      // 失败

    /**
     * 关联订单
     * @return \think\model\relation\BelongsTo
     */
    public function order()
    {
        return $this->belongsTo(Order::class, 'order_id', 'id');
    }

    /**
     * 关联用户
     * @return \think\model\relation\BelongsTo
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    /**
     * 生成交易流水号
     * @return string
     */
    public static function generateTransactionNo(): string
    {
        return 'TXN' . date('YmdHis') . str_pad((string)mt_rand(0, 999999), 6, '0', STR_PAD_LEFT);
    }
}
