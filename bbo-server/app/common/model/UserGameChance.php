<?php
declare(strict_types=1);

namespace app\common\model;

use think\Model;

/**
 * 用户游戏次数模型
 */
class UserGameChance extends Model
{
    protected $table = 'user_game_chances';
    protected $autoWriteTimestamp = false;

    /**
     * 获取用户游戏次数
     */
    public static function getChances(int $userId, string $gameCode): int
    {
        $record = self::where('user_id', $userId)
            ->where('game_code', $gameCode)
            ->find();

        if (!$record) {
            return 0;
        }

        // 检查是否需要重置今日已用次数
        $today = date('Y-m-d');
        if ($record->last_play_date != $today) {
            $record->used_today = 0;
            $record->last_play_date = $today;
            $record->save();
        }

        return (int)$record->chances;
    }

    /**
     * 获取或创建用户游戏次数记录
     */
    public static function getOrCreate(int $userId, string $gameCode): self
    {
        $record = self::where('user_id', $userId)
            ->where('game_code', $gameCode)
            ->find();

        if (!$record) {
            $record = self::create([
                'user_id' => $userId,
                'game_code' => $gameCode,
                'chances' => 0,
                'used_today' => 0,
                'total_used' => 0,
                'last_play_date' => date('Y-m-d'),
            ]);
        }

        return $record;
    }

    /**
     * 增加游戏次数
     */
    public static function addChances(int $userId, string $gameCode, int $amount, string $source, string $sourceId = '', string $ip = ''): bool
    {
        if ($amount <= 0) {
            return false;
        }

        $record = self::getOrCreate($userId, $gameCode);
        $record->chances = $record->chances + $amount;
        $record->save();

        // 记录获取日志
        ChanceLog::create([
            'user_id' => $userId,
            'game_code' => $gameCode,
            'source' => $source,
            'source_id' => $sourceId,
            'chances' => $amount,
            'ip' => $ip,
        ]);

        return true;
    }

    /**
     * 使用游戏次数
     */
    public static function useChance(int $userId, string $gameCode): bool
    {
        $record = self::getOrCreate($userId, $gameCode);

        // 检查是否需要重置今日已用次数
        $today = date('Y-m-d');
        if ($record->last_play_date != $today) {
            $record->used_today = 0;
            $record->last_play_date = $today;
        }

        if ($record->chances <= 0) {
            return false;
        }

        $record->chances = $record->chances - 1;
        $record->used_today = $record->used_today + 1;
        $record->total_used = $record->total_used + 1;
        $record->last_play_date = $today;
        $record->save();

        return true;
    }

    /**
     * 获取用户所有游戏次数
     */
    public static function getAllChances(int $userId): array
    {
        $records = self::where('user_id', $userId)->select()->toArray();
        $result = [];

        $today = date('Y-m-d');
        foreach ($records as $record) {
            $usedToday = $record['used_today'];
            // 如果不是今天，重置今日已用
            if ($record['last_play_date'] != $today) {
                $usedToday = 0;
            }

            $result[] = [
                'game_code' => $record['game_code'],
                'chances' => (int)$record['chances'],
                'used_today' => $usedToday,
                'total_used' => (int)$record['total_used'],
            ];
        }

        return $result;
    }
}
