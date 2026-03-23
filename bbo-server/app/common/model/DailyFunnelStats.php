<?php
declare(strict_types=1);

namespace app\common\model;

use think\Model;

class DailyFunnelStats extends Model
{
    protected $table = 'daily_funnel_stats';
    protected $autoWriteTimestamp = false;

    /**
     * Purchase funnel step definitions
     */
    public const PURCHASE_FUNNEL = [
        ['order' => 1, 'name' => '访问首页', 'event' => 'page_view_home'],
        ['order' => 2, 'name' => '浏览商品列表', 'event' => 'page_view_goods_list'],
        ['order' => 3, 'name' => '查看商品详情', 'event' => 'page_view_goods_detail'],
        ['order' => 4, 'name' => '加购/立即购买', 'event' => 'click_add_cart,click_buy_now'],
        ['order' => 5, 'name' => '进入结算页', 'event' => 'page_view_checkout'],
        ['order' => 6, 'name' => '提交订单', 'event' => 'click_submit_order'],
        ['order' => 7, 'name' => '支付成功', 'event' => 'payment_success'],
    ];

    /**
     * Get funnel stats for date range
     */
    public static function getFunnelStats(string $funnelType, string $startDate, string $endDate): array
    {
        return static::where('funnel_type', $funnelType)
            ->whereBetween('date', [$startDate, $endDate])
            ->field('step_order, step_name, step_event, SUM(uv) as uv, SUM(pv) as pv')
            ->group('step_order, step_name, step_event')
            ->order('step_order', 'asc')
            ->select()
            ->toArray();
    }

    /**
     * Get daily funnel trend
     */
    public static function getFunnelTrend(string $funnelType, string $startDate, string $endDate): array
    {
        return static::where('funnel_type', $funnelType)
            ->whereBetween('date', [$startDate, $endDate])
            ->field('date, step_order, step_name, uv, pv')
            ->order('date', 'asc')
            ->order('step_order', 'asc')
            ->select()
            ->toArray();
    }
}
