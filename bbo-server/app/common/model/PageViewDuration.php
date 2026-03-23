<?php
declare(strict_types=1);

namespace app\common\model;

use think\Model;

class PageViewDuration extends Model
{
    protected $table = 'page_view_durations';
    protected $autoWriteTimestamp = false;

    /**
     * Batch insert page durations
     */
    public static function batchInsert(array $records): int
    {
        if (empty($records)) {
            return 0;
        }
        return (new static())->insertAll($records);
    }

    /**
     * Get page stats with average duration
     */
    public static function getPageStats(string $startDate, string $endDate, int $limit = 50): array
    {
        return static::fieldRaw('page, COUNT(*) as pv, COUNT(DISTINCT CASE WHEN user_id > 0 THEN user_id ELSE session_id END) as uv, ROUND(AVG(duration_ms)) as avg_duration_ms, SUM(CASE WHEN duration_ms < 3000 THEN 1 ELSE 0 END) as bounce_count')
            ->whereTime('created_at', 'between', [$startDate . ' 00:00:00', $endDate . ' 23:59:59'])
            ->group('page')
            ->order('pv', 'desc')
            ->limit($limit)
            ->select()
            ->toArray();
    }

    /**
     * Get duration distribution for a specific page
     */
    public static function getDurationDistribution(string $page, string $startDate, string $endDate): array
    {
        $timeRange = [$startDate . ' 00:00:00', $endDate . ' 23:59:59'];

        $ranges = [
            ['label' => '0-10s', 'min' => 0, 'max' => 10000],
            ['label' => '10-30s', 'min' => 10000, 'max' => 30000],
            ['label' => '30-60s', 'min' => 30000, 'max' => 60000],
            ['label' => '1-3min', 'min' => 60000, 'max' => 180000],
            ['label' => '3min+', 'min' => 180000, 'max' => 999999999],
        ];

        $result = [];
        foreach ($ranges as $range) {
            $count = static::where('page', $page)
                ->whereTime('created_at', 'between', $timeRange)
                ->where('duration_ms', '>=', $range['min'])
                ->where('duration_ms', '<', $range['max'])
                ->count();
            $result[] = [
                'label' => $range['label'],
                'count' => $count,
            ];
        }

        return $result;
    }
}
