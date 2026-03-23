<?php
declare(strict_types=1);

namespace app\common\service;

use app\common\model\UserEvent;
use app\common\model\PageViewDuration;
use app\common\model\DailyFunnelStats;
use app\common\model\DailyPageStats;
use think\facade\Db;

class AnalyticsService
{
    /**
     * Aggregate daily funnel stats for a given date
     */
    public function aggregateDailyFunnel(string $date): void
    {
        $funnelSteps = DailyFunnelStats::PURCHASE_FUNNEL;

        foreach ($funnelSteps as $step) {
            $eventTypes = explode(',', $step['event']);

            $stats = UserEvent::whereIn('event_type', $eventTypes)
                ->whereTime('created_at', 'between', [$date . ' 00:00:00', $date . ' 23:59:59'])
                ->field('COUNT(*) as pv, COUNT(DISTINCT CASE WHEN user_id > 0 THEN user_id ELSE session_id END) as uv')
                ->find();

            DailyFunnelStats::where('date', $date)
                ->where('funnel_type', 'purchase')
                ->where('step_order', $step['order'])
                ->delete();

            DailyFunnelStats::create([
                'date' => $date,
                'funnel_type' => 'purchase',
                'step_order' => $step['order'],
                'step_name' => $step['name'],
                'step_event' => $step['event'],
                'uv' => (int)($stats['uv'] ?? 0),
                'pv' => (int)($stats['pv'] ?? 0),
            ]);
        }
    }

    /**
     * Aggregate daily page stats for a given date
     */
    public function aggregateDailyPageStats(string $date): void
    {
        $pageStats = PageViewDuration::where('created_at', '>=', $date . ' 00:00:00')
            ->where('created_at', '<=', $date . ' 23:59:59')
            ->field('page, COUNT(*) as pv, COUNT(DISTINCT CASE WHEN user_id > 0 THEN user_id ELSE session_id END) as uv, ROUND(AVG(duration_ms)) as avg_duration_ms, SUM(CASE WHEN duration_ms < 3000 THEN 1 ELSE 0 END) as bounce_count')
            ->group('page')
            ->select();

        foreach ($pageStats as $stat) {
            DailyPageStats::where('date', $date)
                ->where('page', $stat['page'])
                ->delete();

            DailyPageStats::create([
                'date' => $date,
                'page' => $stat['page'],
                'pv' => (int)$stat['pv'],
                'uv' => (int)$stat['uv'],
                'avg_duration_ms' => (int)$stat['avg_duration_ms'],
                'bounce_count' => (int)$stat['bounce_count'],
            ]);
        }
    }

    /**
     * Run all daily aggregations for yesterday
     */
    public function runDailyAggregation(): void
    {
        $yesterday = date('Y-m-d', strtotime('-1 day'));
        $this->aggregateDailyFunnel($yesterday);
        $this->aggregateDailyPageStats($yesterday);
    }

    /**
     * Clean old raw events (keep last N days)
     */
    public function cleanOldEvents(int $keepDays = 90): int
    {
        $cutoffDate = date('Y-m-d', strtotime("-{$keepDays} days"));

        $deletedEvents = UserEvent::where('created_at', '<', $cutoffDate . ' 00:00:00')->delete();
        $deletedDurations = PageViewDuration::where('created_at', '<', $cutoffDate . ' 00:00:00')->delete();

        return $deletedEvents + $deletedDurations;
    }

    /**
     * Get real-time funnel data (query raw events directly)
     */
    public function getRealtimeFunnel(string $startDate, string $endDate): array
    {
        $funnelSteps = DailyFunnelStats::PURCHASE_FUNNEL;
        $eventTypes = array_column($funnelSteps, 'event');

        $funnelData = UserEvent::getFunnelData($startDate, $endDate, $eventTypes);

        $result = [];
        $firstUv = 0;
        foreach ($funnelSteps as $index => $step) {
            $data = $funnelData[$step['event']] ?? ['uv' => 0, 'pv' => 0];
            if ($index === 0) {
                $firstUv = $data['uv'];
            }
            $result[] = [
                'step_order' => $step['order'],
                'step_name' => $step['name'],
                'step_event' => $step['event'],
                'uv' => $data['uv'],
                'pv' => $data['pv'],
                'overall_rate' => $firstUv > 0 ? round(($data['uv'] / $firstUv) * 100, 2) : 0,
                'step_rate' => $index > 0
                    ? (($result[$index - 1]['uv'] ?? 0) > 0
                        ? round(($data['uv'] / $result[$index - 1]['uv']) * 100, 2)
                        : 0)
                    : 100,
            ];
        }

        return $result;
    }

    /**
     * Get overview data
     */
    public function getOverview(string $startDate, string $endDate): array
    {
        $todayStats = UserEvent::getOverviewStats(date('Y-m-d'));
        $yesterdayStats = UserEvent::getOverviewStats(date('Y-m-d', strtotime('-1 day')));

        $trend = UserEvent::getDailyTrend($startDate, $endDate);

        // Calculate conversion rate (payment_success / page_view_goods_detail)
        $funnelData = UserEvent::getFunnelData($startDate, $endDate, [
            'page_view_goods_detail',
            'payment_success'
        ]);
        $detailUv = $funnelData['page_view_goods_detail']['uv'] ?? 0;
        $paymentUv = $funnelData['payment_success']['uv'] ?? 0;
        $conversionRate = $detailUv > 0 ? round(($paymentUv / $detailUv) * 100, 2) : 0;

        $topPages = UserEvent::getTopPages($startDate, $endDate);
        $deviceDist = UserEvent::getDeviceDistribution($startDate, $endDate);

        return [
            'today' => $todayStats,
            'yesterday' => $yesterdayStats,
            'conversion_rate' => $conversionRate,
            'trend' => $trend,
            'top_pages' => $topPages,
            'device_distribution' => $deviceDist,
        ];
    }
}
