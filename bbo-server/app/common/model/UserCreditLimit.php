<?php
declare(strict_types=1);

namespace app\common\model;

use think\Model;

/**
 * 用户分期额度模型
 */
class UserCreditLimit extends Model
{
    protected $name = 'user_credit_limits';

    protected $autoWriteTimestamp = 'datetime';

    protected $createTime = 'created_at';

    protected $updateTime = 'updated_at';

    // 自动追加的字段（toArray时自动包含计算属性）
    protected $append = ['available_limit'];

    // 状态常量
    const STATUS_NORMAL = 1;   // 正常
    const STATUS_ACTIVE = 1;   // 正常（别名）
    const STATUS_FROZEN = 2;   // 冻结
    const STATUS_CLOSED = 3;   // 关闭

    /**
     * 关联用户
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * 关联申请
     */
    public function application()
    {
        return $this->belongsTo(CreditApplication::class, 'application_id');
    }

    /**
     * 获取可用额度
     */
    public function getAvailableLimitAttr($value, $data): float
    {
        return max(0, $data['total_limit'] - $data['used_limit'] - $data['frozen_limit']);
    }

    /**
     * 获取用户额度
     */
    public static function getByUser(int $userId): ?self
    {
        return self::where('user_id', $userId)
            ->where('status', self::STATUS_NORMAL)
            ->find();
    }

    /**
     * 检查用户是否有可用额度
     */
    public static function hasAvailableLimit(int $userId, float $amount = 0): bool
    {
        $limit = self::getByUser($userId);
        if (!$limit) {
            return false;
        }
        $available = $limit->total_limit - $limit->used_limit - $limit->frozen_limit;
        return $available >= $amount;
    }

    /**
     * 使用额度
     */
    public function useLimit(float $amount): bool
    {
        if ($this->available_limit < $amount) {
            return false;
        }
        $this->used_limit += $amount;
        $this->last_used_at = date('Y-m-d H:i:s');
        return $this->save();
    }

    /**
     * 释放额度
     */
    public function releaseLimit(float $amount): bool
    {
        $this->used_limit = max(0, $this->used_limit - $amount);
        return $this->save();
    }

    /**
     * 冻结额度
     */
    public function freezeLimit(float $amount): bool
    {
        if ($this->available_limit < $amount) {
            return false;
        }
        $this->frozen_limit += $amount;
        return $this->save();
    }

    /**
     * 解冻额度
     */
    public function unfreezeLimit(float $amount): bool
    {
        $this->frozen_limit = max(0, $this->frozen_limit - $amount);
        return $this->save();
    }

    /**
     * 冻结转已用（确认分期时）
     */
    public function confirmFrozenToUsed(float $amount): bool
    {
        $this->frozen_limit = max(0, $this->frozen_limit - $amount);
        $this->used_limit += $amount;
        $this->last_used_at = date('Y-m-d H:i:s');
        return $this->save();
    }
}
