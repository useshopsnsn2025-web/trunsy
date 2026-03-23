<?php
declare(strict_types=1);

namespace app\common\model;

use think\Model;

/**
 * 平台结算模型
 */
class Settlement extends Model
{
    protected $name = 'settlements';
    protected $pk = 'id';
    protected $autoWriteTimestamp = 'datetime';
    protected $createTime = 'created_at';
    protected $updateTime = false;

    protected $type = [
        'id' => 'integer',
        'user_id' => 'integer',
        'order_id' => 'integer',
        'order_amount' => 'float',
        'commission_rate' => 'float',
        'commission_amount' => 'float',
        'settlement_amount' => 'float',
        'status' => 'integer',
    ];

    // 状态常量
    const STATUS_PENDING = 0;   // 待结算
    const STATUS_SETTLED = 1;   // 已结算

    /**
     * 生成结算单号
     */
    public static function generateNo(): string
    {
        return 'STL' . date('YmdHis') . mt_rand(1000, 9999);
    }

    /**
     * 关联用户
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    /**
     * 关联订单
     */
    public function orderInfo()
    {
        return $this->belongsTo(Order::class, 'order_id', 'id');
    }
}
