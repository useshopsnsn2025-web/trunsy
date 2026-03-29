<?php
declare(strict_types=1);

namespace app\common\model;

use think\Model;
use think\facade\Db;

class UserEvent extends Model
{
    protected $table = 'user_events';
    protected $autoWriteTimestamp = false;

    protected $json = ['properties'];
    protected $jsonAssoc = true;

    /**
     * Batch insert events
     */
    public static function batchInsert(array $events): int
    {
        if (empty($events)) {
            return 0;
        }
        return (new static())->insertAll($events);
    }

    /**
     * Get funnel data for a date range
     */
    public static function getFunnelData(string $startDate, string $endDate, array $eventTypes): array
    {
        $result = [];
        foreach ($eventTypes as $eventType) {
            // Support comma-separated event types (e.g. "click_add_cart,click_buy_now")
            $types = explode(',', $eventType);

            $query = static::whereIn('event_type', $types)
                ->whereTime('created_at', 'between', [$startDate . ' 00:00:00', $endDate . ' 23:59:59']);

            $uvRow = (clone $query)->fieldRaw('COUNT(DISTINCT CASE WHEN user_id > 0 THEN user_id ELSE session_id END) as uv_count')
                ->find();
            $result[$eventType] = [
                'uv' => (int)($uvRow['uv_count'] ?? 0),
                'pv' => (clone $query)->count(),
            ];
        }
        return $result;
    }

    /**
     * Get daily event counts for trend chart
     */
    public static function getDailyTrend(string $startDate, string $endDate, string $eventType = ''): array
    {
        $query = static::fieldRaw('DATE(created_at) as date, COUNT(*) as pv, COUNT(DISTINCT CASE WHEN user_id > 0 THEN user_id ELSE session_id END) as uv')
            ->whereTime('created_at', 'between', [$startDate . ' 00:00:00', $endDate . ' 23:59:59'])
            ->group('DATE(created_at)')
            ->order('date', 'asc');

        if ($eventType) {
            $types = explode(',', $eventType);
            $query->whereIn('event_type', $types);
        }

        return $query->select()->toArray();
    }

    /**
     * Get top pages by PV
     */
    public static function getTopPages(string $startDate, string $endDate, int $limit = 10): array
    {
        return static::fieldRaw('page, COUNT(*) as pv, COUNT(DISTINCT CASE WHEN user_id > 0 THEN user_id ELSE session_id END) as uv')
            ->where('event_category', 'page')
            ->where('page', '<>', '')
            ->whereTime('created_at', 'between', [$startDate . ' 00:00:00', $endDate . ' 23:59:59'])
            ->group('page')
            ->order('pv', 'desc')
            ->limit($limit)
            ->select()
            ->toArray();
    }

    /**
     * Get goods conversion data
     */
    public static function getGoodsConversion(string $startDate, string $endDate, int $limit = 20): array
    {
        $timeRange = [$startDate . ' 00:00:00', $endDate . ' 23:59:59'];

        $views = static::where('event_type', 'page_view_goods_detail')
            ->whereTime('created_at', 'between', $timeRange)
            ->where('target', '<>', '')
            ->fieldRaw('target as goods_id, COUNT(*) as view_count, COUNT(DISTINCT CASE WHEN user_id > 0 THEN user_id ELSE session_id END) as view_uv')
            ->group('target')
            ->order('view_count', 'desc')
            ->limit($limit)
            ->select()
            ->toArray();

        if (empty($views)) {
            return [];
        }

        $goodsIds = array_column($views, 'goods_id');

        $carts = static::where('event_type', 'click_add_cart')
            ->whereTime('created_at', 'between', $timeRange)
            ->whereIn('target', $goodsIds)
            ->fieldRaw('target as goods_id, COUNT(DISTINCT CASE WHEN user_id > 0 THEN user_id ELSE session_id END) as cart_uv')
            ->group('target')
            ->select()
            ->column('cart_uv', 'goods_id');

        $buys = static::where('event_type', 'click_buy_now')
            ->whereTime('created_at', 'between', $timeRange)
            ->whereIn('target', $goodsIds)
            ->fieldRaw('target as goods_id, COUNT(DISTINCT CASE WHEN user_id > 0 THEN user_id ELSE session_id END) as buy_uv')
            ->group('target')
            ->select()
            ->column('buy_uv', 'goods_id');

        $favorites = static::where('event_type', 'click_favorite')
            ->whereTime('created_at', 'between', $timeRange)
            ->whereIn('target', $goodsIds)
            ->fieldRaw('target as goods_id, COUNT(DISTINCT CASE WHEN user_id > 0 THEN user_id ELSE session_id END) as fav_uv')
            ->group('target')
            ->select()
            ->column('fav_uv', 'goods_id');

        foreach ($views as &$item) {
            $gid = $item['goods_id'];
            $item['cart_uv'] = $carts[$gid] ?? 0;
            $item['buy_uv'] = $buys[$gid] ?? 0;
            $item['fav_uv'] = $favorites[$gid] ?? 0;
            $item['cart_rate'] = $item['view_uv'] > 0 ? min(100, round(($item['cart_uv'] / $item['view_uv']) * 100, 2)) : 0;
            $item['buy_rate'] = $item['view_uv'] > 0 ? min(100, round(($item['buy_uv'] / $item['view_uv']) * 100, 2)) : 0;
            $item['fav_rate'] = $item['view_uv'] > 0 ? min(100, round(($item['fav_uv'] / $item['view_uv']) * 100, 2)) : 0;
        }

        return $views;
    }

    /**
     * Get overview stats for a specific date
     */
    public static function getOverviewStats(string $date): array
    {
        $timeRange = [$date . ' 00:00:00', $date . ' 23:59:59'];

        return [
            'pv' => static::whereTime('created_at', 'between', $timeRange)->count(),
            'uv' => (int)(static::whereTime('created_at', 'between', $timeRange)
                ->fieldRaw('COUNT(DISTINCT CASE WHEN user_id > 0 THEN user_id ELSE session_id END) as uv_count')
                ->find()['uv_count'] ?? 0),
            'conversion_count' => static::where('event_category', 'conversion')
                ->whereTime('created_at', 'between', $timeRange)->count(),
        ];
    }

    /**
     * Get device type distribution
     */
    public static function getDeviceDistribution(string $startDate, string $endDate): array
    {
        return static::fieldRaw('device_type, COUNT(DISTINCT CASE WHEN user_id > 0 THEN user_id ELSE session_id END) as uv')
            ->where('device_type', '<>', '')
            ->whereTime('created_at', 'between', [$startDate . ' 00:00:00', $endDate . ' 23:59:59'])
            ->group('device_type')
            ->select()
            ->toArray();
    }
}
