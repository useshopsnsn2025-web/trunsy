<?php
declare(strict_types=1);

namespace app\common\model;

use think\Model;

/**
 * 每日奖品统计模型
 */
class DailyPrizeStats extends Model
{
    protected $table = 'daily_prize_stats';
    protected $autoWriteTimestamp = false;

    /**
     * 获取今日奖品发放数量
     */
    public static function getTodayIssued(int $prizeId): int
    {
        $today = date('Y-m-d');
        $record = self::where('stat_date', $today)
            ->where('prize_id', $prizeId)
            ->find();

        return $record ? (int)$record->issued_count : 0;
    }

    /**
     * 增加发放统计
     */
    public static function incrementIssued(int $gameId, int $prizeId, float $value): void
    {
        $today = date('Y-m-d');

        $record = self::where('stat_date', $today)
            ->where('prize_id', $prizeId)
            ->find();

        if ($record) {
            $record->issued_count = $record->issued_count + 1;
            $record->issued_value = $record->issued_value + $value;
            $record->save();
        } else {
            self::create([
                'stat_date' => $today,
                'game_id' => $gameId,
                'prize_id' => $prizeId,
                'issued_count' => 1,
                'issued_value' => $value,
            ]);
        }
    }

    /**
     * 获取游戏每日统计
     */
    public static function getGameDailyStats(int $gameId, string $date = null): array
    {
        if (!$date) {
            $date = date('Y-m-d');
        }

        $records = self::where('stat_date', $date)
            ->where('game_id', $gameId)
            ->select()
            ->toArray();

        $totalCount = 0;
        $totalValue = 0;
        $prizeStats = [];

        foreach ($records as $record) {
            $totalCount += $record['issued_count'];
            $totalValue += $record['issued_value'];
            $prizeStats[$record['prize_id']] = [
                'count' => (int)$record['issued_count'],
                'value' => (float)$record['issued_value'],
            ];
        }

        return [
            'date' => $date,
            'total_count' => $totalCount,
            'total_value' => $totalValue,
            'prize_stats' => $prizeStats,
        ];
    }

    /**
     * 获取日期范围内的统计
     */
    public static function getStatsRange(int $gameId, string $startDate, string $endDate): array
    {
        return self::where('game_id', $gameId)
            ->where('stat_date', '>=', $startDate)
            ->where('stat_date', '<=', $endDate)
            ->field('stat_date, SUM(issued_count) as total_count, SUM(issued_value) as total_value')
            ->group('stat_date')
            ->order('stat_date', 'asc')
            ->select()
            ->toArray();
    }
}
