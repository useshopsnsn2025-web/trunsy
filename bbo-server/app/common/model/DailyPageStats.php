<?php
declare(strict_types=1);

namespace app\common\model;

use think\Model;

class DailyPageStats extends Model
{
    protected $table = 'daily_page_stats';
    protected $autoWriteTimestamp = false;

    /**
     * Get page stats for date range
     */
    public static function getPageStats(string $startDate, string $endDate): array
    {
        return static::whereBetween('date', [$startDate, $endDate])
            ->field('page, SUM(pv) as pv, SUM(uv) as uv, ROUND(AVG(avg_duration_ms)) as avg_duration_ms, SUM(bounce_count) as bounce_count')
            ->group('page')
            ->order('pv', 'desc')
            ->select()
            ->toArray();
    }

    /**
     * Get daily trend for a specific page
     */
    public static function getPageTrend(string $page, string $startDate, string $endDate): array
    {
        return static::where('page', $page)
            ->whereBetween('date', [$startDate, $endDate])
            ->field('date, pv, uv, avg_duration_ms, bounce_count')
            ->order('date', 'asc')
            ->select()
            ->toArray();
    }
}
