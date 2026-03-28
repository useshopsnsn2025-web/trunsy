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
        $funnelType = input('get.funnel_type', 'purchase');

        // If date range is recent (includes today), use realtime query
        $service = new AnalyticsService();
        $today = date('Y-m-d');

        if ($endDate >= $today) {
            // Use realtime calculation
            $steps = $service->getRealtimeFunnel($startDate, $endDate);
        } else {
            // Use aggregated data
            $rawSteps = DailyFunnelStats::getFunnelStats($funnelType, $startDate, $endDate);

            $steps = [];
            $firstUv = 0;
            foreach ($rawSteps as $index => $step) {
                $uv = (int)$step['uv'];
                if ($index === 0) {
                    $firstUv = $uv;
                }
                $steps[] = [
                    'step_order' => (int)$step['step_order'],
                    'step_name' => $step['step_name'],
                    'step_event' => $step['step_event'],
                    'uv' => $uv,
                    'pv' => (int)$step['pv'],
                    'overall_rate' => $firstUv > 0 ? round(($uv / $firstUv) * 100, 2) : 0,
                    'step_rate' => $index > 0
                        ? (($steps[$index - 1]['uv'] ?? 0) > 0
                            ? round(($uv / $steps[$index - 1]['uv']) * 100, 2)
                            : 0)
                        : 100,
                ];
            }
        }

        // Get trend data
        $trend = DailyFunnelStats::getFunnelTrend($funnelType, $startDate, $endDate);

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

        $today = date('Y-m-d');

        // Use realtime data if range includes today
        if ($endDate >= $today) {
            $stats = PageViewDuration::getPageStats($startDate, $endDate);
        } else {
            $stats = DailyPageStats::getPageStats($startDate, $endDate);
        }

        // Calculate bounce rate for each page
        foreach ($stats as &$stat) {
            $stat['bounce_rate'] = $stat['pv'] > 0
                ? round(($stat['bounce_count'] / $stat['pv']) * 100, 2)
                : 0;
        }

        $result = ['list' => $stats];

        // If specific page requested, include distribution and trend
        if ($page) {
            $result['distribution'] = PageViewDuration::getDurationDistribution($page, $startDate, $endDate);
            $result['trend'] = DailyPageStats::getPageTrend($page, $startDate, $endDate);
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
            $goodsModels = Goods::whereIn('id', $goodsIds)
                ->field('id, images, price, currency, cover_image')
                ->select();

            // Get translations for titles
            $locale = request()->header('Accept-Language', 'zh-tw');
            Goods::appendTranslations($goodsModels, $locale);

            // Build lookup by id
            $goodsList = [];
            foreach ($goodsModels as $g) {
                $goodsList[(int)$g->id] = $g;
            }

            foreach ($conversionData as &$item) {
                $goodsId = (int)$item['goods_id'];
                $goods = $goodsList[$goodsId] ?? null;
                if ($goods) {
                    $item['goods_title'] = $goods->getTranslated('title', $locale);
                    // 优先使用 cover_image，否则取 images 第一张
                    $coverImage = $goods->getData('cover_image') ?? '';
                    if (empty($coverImage)) {
                        $images = array_values((array)($goods['images'] ?? []));
                        $coverImage = $images[0] ?? '';
                    }
                    $item['goods_image'] = !empty($coverImage) ? \app\common\helper\UrlHelper::getFullUrl($coverImage) : '';
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
