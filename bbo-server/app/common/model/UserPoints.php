<?php
declare(strict_types=1);

namespace app\common\model;

use think\Model;
use think\facade\Db;

/**
 * 用户积分模型
 */
class UserPoints extends Model
{
    protected $table = 'user_points';
    protected $autoWriteTimestamp = true;
    protected $createTime = 'created_at';
    protected $updateTime = 'updated_at';

    /**
     * 获取用户积分信息
     */
    public static function getByUserId(int $userId): ?self
    {
        return self::where('user_id', $userId)->find();
    }

    /**
     * 获取或创建用户积分记录
     */
    public static function getOrCreate(int $userId): self
    {
        $record = self::where('user_id', $userId)->find();
        if (!$record) {
            $record = self::create([
                'user_id' => $userId,
                'balance' => 0,
                'total_earned' => 0,
                'total_spent' => 0,
            ]);
        }
        return $record;
    }

    /**
     * 增加积分
     */
    public static function addPoints(int $userId, int $amount, string $source, string $sourceId = '', string $description = ''): bool
    {
        if ($amount <= 0) {
            return false;
        }

        Db::startTrans();
        try {
            $record = self::getOrCreate($userId);
            $newBalance = $record->balance + $amount;

            // 更新积分余额
            $record->balance = $newBalance;
            $record->total_earned = $record->total_earned + $amount;
            $record->save();

            // 记录流水
            PointLog::create([
                'user_id' => $userId,
                'type' => 'earn',
                'amount' => $amount,
                'balance_after' => $newBalance,
                'source' => $source,
                'source_id' => $sourceId,
                'description' => $description,
            ]);

            Db::commit();
            return true;
        } catch (\Exception $e) {
            Db::rollback();
            \think\facade\Log::error('Add points failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * 扣减积分
     */
    public static function deductPoints(int $userId, int $amount, string $source, string $sourceId = '', string $description = ''): bool
    {
        if ($amount <= 0) {
            return false;
        }

        Db::startTrans();
        try {
            $record = self::getOrCreate($userId);

            // 检查余额是否足够
            if ($record->balance < $amount) {
                Db::rollback();
                return false;
            }

            $newBalance = $record->balance - $amount;

            // 更新积分余额
            $record->balance = $newBalance;
            $record->total_spent = $record->total_spent + $amount;
            $record->save();

            // 记录流水
            PointLog::create([
                'user_id' => $userId,
                'type' => 'spend',
                'amount' => $amount,
                'balance_after' => $newBalance,
                'source' => $source,
                'source_id' => $sourceId,
                'description' => $description,
            ]);

            Db::commit();
            return true;
        } catch (\Exception $e) {
            Db::rollback();
            \think\facade\Log::error('Deduct points failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * 获取用户积分余额
     */
    public static function getBalance(int $userId): int
    {
        $record = self::where('user_id', $userId)->find();
        return $record ? (int)$record->balance : 0;
    }

    /**
     * 转换为 API 数组
     */
    public function toApiArray(): array
    {
        return [
            'balance' => (int)$this->balance,
            'total_earned' => (int)$this->total_earned,
            'total_spent' => (int)$this->total_spent,
            'cash_value' => number_format($this->balance / 1000, 2), // 1000积分=$1
        ];
    }
}
