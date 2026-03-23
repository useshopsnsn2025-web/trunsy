<?php
declare(strict_types=1);

namespace app\common\model;

use think\Model;

/**
 * 用户钱包模型
 */
class UserWallet extends Model
{
    protected $name = 'user_wallets';
    protected $pk = 'id';
    protected $autoWriteTimestamp = 'datetime';
    protected $createTime = false;
    protected $updateTime = 'updated_at';

    protected $type = [
        'id' => 'integer',
        'user_id' => 'integer',
        'balance' => 'float',
        'frozen' => 'float',
        'total_income' => 'float',
        'total_withdraw' => 'float',
    ];

    /**
     * 关联用户
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    /**
     * 获取或创建用户钱包
     * @param int $userId
     * @return UserWallet
     */
    public static function getOrCreate(int $userId): UserWallet
    {
        $wallet = self::where('user_id', $userId)->find();

        if (!$wallet) {
            $wallet = self::create([
                'user_id' => $userId,
                'balance' => 0,
                'frozen' => 0,
                'total_income' => 0,
                'total_withdraw' => 0,
            ]);
        }

        return $wallet;
    }

    /**
     * 增加余额
     * @param float $amount
     * @return bool
     */
    public function addBalance(float $amount): bool
    {
        $this->balance += $amount;
        $this->total_income += $amount;
        return $this->save();
    }

    /**
     * 扣减余额
     * @param float $amount
     * @return bool
     */
    public function deductBalance(float $amount): bool
    {
        if ($this->balance < $amount) {
            return false;
        }
        $this->balance -= $amount;
        return $this->save();
    }

    /**
     * 冻结金额
     * @param float $amount
     * @return bool
     */
    public function freeze(float $amount): bool
    {
        if ($this->balance < $amount) {
            return false;
        }
        $this->balance -= $amount;
        $this->frozen += $amount;
        return $this->save();
    }

    /**
     * 解冻金额
     * @param float $amount
     * @return bool
     */
    public function unfreeze(float $amount): bool
    {
        if ($this->frozen < $amount) {
            return false;
        }
        $this->frozen -= $amount;
        $this->balance += $amount;
        return $this->save();
    }

    /**
     * 确认提现（从冻结金额扣除）
     * @param float $amount
     * @return bool
     */
    public function confirmWithdraw(float $amount): bool
    {
        if ($this->frozen < $amount) {
            return false;
        }
        $this->frozen -= $amount;
        $this->total_withdraw += $amount;
        return $this->save();
    }
}
