<?php
declare(strict_types=1);

namespace app\admin\controller;

use think\Response;
use app\common\service\AnalyticsService;
use app\common\model\UserEvent;
use app\common\model\PageViewDuration;
use app\common\model\DailyFunnelStats;
use app\common\model\DailyPageStats;
use app\common\model\Goods;

class Analytics extends Base
{
    /**
     * Overview data
     * GET /admin/analytics/overview
     */
    public function overview(): Response
    {
        $startDate = input('get.start_date', date('Y-m-d', strtotime('-7 days')));
        $endDate = input('get.end_date', date('Y-m-d'));

        $service = new AnalyticsService();
        $data = $service->getOverview($startDate, $endDate);

        return $this->success($data);
    }

    /**
     * Funnel data
     * GET /admin/analytics/funnel
     */
    public function funnel(): Response
    {
        $startDate = input('get.start_date', date('Y-m-d', strtotime('-7 days')));
        $endDate = input('get.end_date', date('Y-m-d'));

        // 始终使用实时查询
        $service = new AnalyticsService();
        $steps = $service->getRealtimeFunnel($startDate, $endDate);

        // 实时趋势：按天查询漏斗第一步和最后一步
        $trend = UserEvent::getDailyFunnelTrend($startDate, $endDate);

        return $this->success([
            'steps' => $steps,
            'trend' => $trend,
        ]);
    }

    /**
     * Page analysis
     * GET /admin/analytics/page-stats
     */
    public function pageStats(): Response
    {
        $startDate = input('get.start_date', date('Y-m-d', strtotime('-7 days')));
        $endDate = input('get.end_date', date('Y-m-d'));
        $page = input('get.page', '');

        // 始终使用实时查询 user_events 表
        $stats = UserEvent::getPageStats($startDate, $endDate);

        $result = ['list' => $stats];

        // If specific page requested, include distribution and trend
        if ($page) {
            $result['distribution'] = PageViewDuration::getDurationDistribution($page, $startDate, $endDate);
            $result['trend'] = UserEvent::getPageTrend($page, $startDate, $endDate);
        }

        return $this->success($result);
    }

    /**
     * Goods conversion analysis
     * GET /admin/analytics/goods-conversion
     */
    public function goodsConversion(): Response
    {
        $startDate = input('get.start_date', date('Y-m-d', strtotime('-7 days')));
        $endDate = input('get.end_date', date('Y-m-d'));
        $limit = min(50, max(10, (int)input('get.limit', 20)));

        $conversionData = UserEvent::getGoodsConversion($startDate, $endDate, $limit);

        // Enrich with goods info
        if (!empty($conversionData)) {
            $goodsIds = array_column($conversionData, 'goods_id');
            $goodsCollection = Goods::whereIn('id', $goodsIds)
                ->field('id, images, price, currency')
                ->select();

            // Get translations for titles
            $locale = request()->header('Accept-Language', 'zh-tw');
            $goodsArray = $goodsCollection->all();
            Goods::appendTranslations($goodsArray, $locale);

            // Build lookup by id
            $goodsList = [];
            foreach ($goodsArray as $g) {
                $goodsList[(int)$g->id] = $g;
            }

            foreach ($conversionData as &$item) {
                $goodsId = (int)$item['goods_id'];
                $goods = $goodsList[$goodsId] ?? null;
                if ($goods) {
                    $item['goods_title'] = $goods->getTranslated('title', $locale);
                    $images = array_values((array)($goods['images'] ?? []));
                    $item['goods_image'] = !empty($images) ? \app\common\helper\UrlHelper::getFullUrl($images[0]) : '';
                    $item['goods_price'] = (float)($goods['price'] ?? 0);
                    $item['goods_currency'] = $goods['currency'] ?? 'SGD';
                } else {
                    $item['goods_title'] = 'Unknown';
                    $item['goods_image'] = '';
                    $item['goods_price'] = 0;
                    $item['goods_currency'] = 'SGD';
                }
            }
        }

        return $this->success(['list' => $conversionData]);
    }

    /**
     * Aggregate data manually (for backfill)
     * POST /admin/analytics/aggregate
     */
    public function aggregate(): Response
    {
        $date = input('post.date', date('Y-m-d', strtotime('-1 day')));

        $service = new AnalyticsService();
        $service->aggregateDailyFunnel($date);
        $service->aggregateDailyPageStats($date);

        return $this->success(null, 'Aggregation completed for ' . $date);
    }
}
