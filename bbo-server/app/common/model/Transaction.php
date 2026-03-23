<?php
declare(strict_types=1);

namespace app\common\model;

use think\Model;

/**
 * 交易流水模型
 */
class Transaction extends Model
{
    protected $name = 'transactions';
    protected $pk = 'id';
    protected $autoWriteTimestamp = false;
    protected $createTime = 'created_at';
    protected $updateTime = false;

    protected $type = [
        'id' => 'integer',
        'user_id' => 'integer',
        'type' => 'integer',
        'amount' => 'float',
        'balance' => 'float',
        'order_id' => 'integer',
        'status' => 'integer',
    ];

    // 交易类型常量
    const TYPE_INCOME = 1;     // 收入
    const TYPE_EXPENSE = 2;    // 支出
    const TYPE_FREEZE = 3;     // 冻结
    const TYPE_UNFREEZE = 4;   // 解冻

    // 状态常量
    const STATUS_PENDING = 0;  // 处理中
    const STATUS_SUCCESS = 1;  // 成功
    const STATUS_FAILED = 2;   // 失败

    public static function getTypeNames(): array
    {
        return [
            self::TYPE_INCOME => '收入',
            self::TYPE_EXPENSE => '支出',
            self::TYPE_FREEZE => '冻结',
            self::TYPE_UNFREEZE => '解冻',
        ];
    }

    /**
     * 生成交易流水号
     */
    public static function generateNo(): string
    {
        return 'TXN' . date('YmdHis') . mt_rand(1000, 9999);
    }

    /**
     * 关联用户
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }
}
