<?php
declare(strict_types=1);

namespace app\common\model;

use think\Model;

/**
 * 用户优惠券模型
 */
class UserCoupon extends Model
{
    protected $name = 'user_coupons';
    protected $pk = 'id';
    protected $autoWriteTimestamp = false;

    protected $type = [
        'id' => 'integer',
        'user_id' => 'integer',
        'coupon_id' => 'integer',
        'order_id' => 'integer',
        'status' => 'integer',
    ];

    // 状态常量
    const STATUS_UNUSED = 0;   // 未使用
    const STATUS_USED = 1;     // 已使用
    const STATUS_EXPIRED = 2;  // 已过期

    /**
     * 关联优惠券
     */
    public function coupon()
    {
        return $this->belongsTo(Coupon::class, 'coupon_id', 'id');
    }

    /**
     * 关联用户
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    /**
     * 判断是否已过期
     */
    public function isExpired(): bool
    {
        return strtotime($this->expired_at) < time();
    }

    /**
     * 获取实际状态（考虑过期）
     */
    public function getActualStatus(): int
    {
        if ($this->status === self::STATUS_UNUSED && $this->isExpired()) {
            return self::STATUS_EXPIRED;
        }
        return $this->status;
    }
}
