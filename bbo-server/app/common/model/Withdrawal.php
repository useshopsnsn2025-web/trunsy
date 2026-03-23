<?php
declare(strict_types=1);

namespace app\common\model;

use think\Model;

/**
 * 提现申请模型
 */
class Withdrawal extends Model
{
    protected $name = 'withdrawals';
    protected $pk = 'id';
    protected $autoWriteTimestamp = 'datetime';
    protected $createTime = 'created_at';
    protected $updateTime = 'updated_at';

    protected $type = [
        'id' => 'integer',
        'user_id' => 'integer',
        'amount' => 'float',
        'fee' => 'float',
        'actual_amount' => 'float',
        'method' => 'integer',
        'status' => 'integer',
        'admin_id' => 'integer',
    ];

    // 提现方式常量
    const METHOD_BANK = 1;     // 银行卡
    const METHOD_ALIPAY = 2;   // 支付宝
    const METHOD_WECHAT = 3;   // 微信

    // 状态常量
    const STATUS_PENDING = 0;      // 待审核
    const STATUS_PROCESSING = 1;   // 处理中
    const STATUS_COMPLETED = 2;    // 已完成
    const STATUS_REJECTED = 3;     // 已拒绝

    public static function getMethodNames(): array
    {
        return [
            self::METHOD_BANK => '银行卡',
            self::METHOD_ALIPAY => '支付宝',
            self::METHOD_WECHAT => '微信',
        ];
    }

    public static function getStatusNames(): array
    {
        return [
            self::STATUS_PENDING => '待审核',
            self::STATUS_PROCESSING => '处理中',
            self::STATUS_COMPLETED => '已完成',
            self::STATUS_REJECTED => '已拒绝',
        ];
    }

    /**
     * 生成提现单号
     */
    public static function generateNo(): string
    {
        return 'WD' . date('YmdHis') . mt_rand(1000, 9999);
    }

    /**
     * 关联用户
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }
}
