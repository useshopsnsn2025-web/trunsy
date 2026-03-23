<?php
declare(strict_types=1);

namespace app\common\model;

use think\Model;

/**
 * 额度变动记录模型
 */
class CreditLimitLog extends Model
{
    protected $name = 'credit_limit_logs';

    protected $autoWriteTimestamp = false;

    protected $createTime = 'created_at';

    // 变动类型
    const TYPE_GRANT = 'grant';         // 授予额度
    const TYPE_USE = 'use';             // 使用额度
    const TYPE_RELEASE = 'release';     // 释放额度
    const TYPE_FREEZE = 'freeze';       // 冻结额度
    const TYPE_UNFREEZE = 'unfreeze';   // 解冻额度
    const TYPE_ADJUST = 'adjust';       // 调整额度

    /**
     * 关联用户
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * 记录额度变动
     */
    public static function log(
        int $userId,
        string $type,
        float $amount,
        float $beforeLimit,
        float $afterLimit,
        float $beforeUsed,
        float $afterUsed,
        ?int $relatedId = null,
        ?string $relatedType = null,
        ?string $remark = null,
        ?int $operatorId = null
    ): self {
        $log = new self();
        $log->data([
            'user_id' => $userId,
            'type' => $type,
            'amount' => $amount,
            'before_limit' => $beforeLimit,
            'after_limit' => $afterLimit,
            'before_used' => $beforeUsed,
            'after_used' => $afterUsed,
            'related_id' => $relatedId,
            'related_type' => $relatedType,
            'remark' => $remark,
            'operator_id' => $operatorId,
            'created_at' => date('Y-m-d H:i:s'),
        ]);
        $log->save();

        return $log;
    }
}
