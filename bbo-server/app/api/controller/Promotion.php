<?php
declare(strict_types=1);

namespace app\api\controller;

use think\Response;
use app\common\model\Promotion as PromotionModel;
use app\common\model\PromotionGoods;
use app\common\model\UserFavorite;
use app\common\helper\UrlHelper;

/**
 * 活动控制器（前端API）
 */
class Promotion extends Base
{
    /**
     * 获取活动列表（进行中的）
     * @return Response
     */
    public function index(): Response
    {
        $page = (int) input('get.page', 1);
        $pageSize = (int) input('get.pageSize', 10);
        $type = input('get.type');

        $page = max(1, $page);
        $pageSize = min(50, max(1, $pageSize));

        try {
            $query = PromotionModel::where('status', PromotionModel::STATUS_RUNNING)
                ->where('start_time', '<=', date('Y-m-d H:i:s'))
                ->where('end_time', '>=', date('Y-m-d H:i:s'));

            // 按类型筛选
            if ($type !== null && $type !== '') {
                $query->where('type', (int) $type);
            }

            $total = $query->count();

            $promotions = $query->order('sort', 'desc')
                ->order('id', 'desc')
                ->page($page, $pageSize)
                ->select();

            $list = [];
            foreach ($promotions as $item) {
                $list[] = $this->formatPromotion($item);
            }

            return $this->success([
                'list' => $list,
                'total' => $total,
                'page' => $page,
                'pageSize' => $pageSize,
                'totalPages' => ceil($total / $pageSize),
            ]);
        } catch (\Exception $e) {
            return $this->error(lang('Operation failed'));
        }
    }

    /**
     * 获取活动详情
     * @param int $id
     * @return Response
     */
    public function read(int $id): Response
    {
        try {
            $promotion = PromotionModel::where('id', $id)
                ->where('status', PromotionModel::STATUS_RUNNING)
                ->find();

            if (!$promotion) {
                return $this->error(lang('Promotion not found'), 404);
            }

            return $this->success($this->formatPromotion($promotion));
        } catch (\Exception $e) {
            return $this->error(lang('Operation failed'));
        }
    }

    /**
     * 获取活动商品列表
     * @param int $id
     * @return Response
     */
    public function goods(int $id): Response
    {
        $page = (int) input('get.page', 1);
        $pageSize = (int) input('get.pageSize', 20);

        $page = max(1, $page);
        $pageSize = min(50, max(1, $pageSize));

        try {
            // 验证活动存在且进行中
            $promotion = PromotionModel::where('id', $id)
                ->where('status', PromotionModel::STATUS_RUNNING)
                ->find();

            if (!$promotion) {
                return $this->error(lang('Promotion not found'), 404);
            }

            $query = PromotionGoods::where('promotion_id', $id)
                ->with(['goods' => function($q) {
                    $q->where('status', 1); // 只查询上架商品
                }]);

            $total = $query->count();

            $items = $query->page($page, $pageSize)->select();

            $list = [];
            foreach ($items as $item) {
                if ($item->goods) { // 只返回有效商品
                    $list[] = $item->toApiArray($this->locale);
                }
            }

            // 检查收藏状态
            if ($this->userId && !empty($list)) {
                $goodsIds = array_column(array_column($list, 'goods'), 'id');
                $likedIds = UserFavorite::where('user_id', $this->userId)
                    ->whereIn('goods_id', $goodsIds)
                    ->column('goods_id');

                foreach ($list as &$item) {
                    $item['goods']['isLiked'] = in_array($item['goods']['id'], $likedIds);
                }
            }

            return $this->success([
                'list' => $list,
                'total' => $total,
                'page' => $page,
                'pageSize' => $pageSize,
                'totalPages' => ceil($total / $pageSize),
            ]);
        } catch (\Exception $e) {
            return $this->error(lang('Operation failed'));
        }
    }

    /**
     * 获取首页活动数据
     * @return Response
     */
    public function home(): Response
    {
        try {
            $now = date('Y-m-d H:i:s');

            // 获取进行中的秒杀活动
            $seckill = PromotionModel::where('status', PromotionModel::STATUS_RUNNING)
                ->where('type', PromotionModel::TYPE_SECKILL)
                ->where('start_time', '<=', $now)
                ->where('end_time', '>=', $now)
                ->order('sort', 'desc')
                ->find();

            // 获取进行中的限时折扣
            $discount = PromotionModel::where('status', PromotionModel::STATUS_RUNNING)
                ->where('type', PromotionModel::TYPE_DISCOUNT)
                ->where('start_time', '<=', $now)
                ->where('end_time', '>=', $now)
                ->order('sort', 'desc')
                ->find();

            // 获取其他进行中的活动
            $promotions = PromotionModel::where('status', PromotionModel::STATUS_RUNNING)
                ->where('start_time', '<=', $now)
                ->where('end_time', '>=', $now)
                ->whereNotIn('type', [PromotionModel::TYPE_SECKILL])
                ->order('sort', 'desc')
                ->limit(4)
                ->select();

            $result = [
                'promotions' => [],
            ];

            // 秒杀活动附带商品
            if ($seckill) {
                $seckillGoods = PromotionGoods::where('promotion_id', $seckill->id)
                    ->with(['goods' => function($q) {
                        $q->where('status', 1);
                    }])
                    ->limit(6)
                    ->select();

                $goodsList = [];
                foreach ($seckillGoods as $item) {
                    if ($item->goods) {
                        $goodsList[] = $item->toApiArray($this->locale);
                    }
                }

                $result['seckill'] = array_merge(
                    $this->formatPromotion($seckill),
                    ['goods' => $goodsList]
                );
            }

            // 限时折扣附带商品
            if ($discount) {
                $discountGoods = PromotionGoods::where('promotion_id', $discount->id)
                    ->with(['goods' => function($q) {
                        $q->where('status', 1);
                    }])
                    ->limit(6)
                    ->select();

                $goodsList = [];
                foreach ($discountGoods as $item) {
                    if ($item->goods) {
                        $goodsList[] = $item->toApiArray($this->locale);
                    }
                }

                $result['discount'] = array_merge(
                    $this->formatPromotion($discount),
                    ['goods' => $goodsList]
                );
            }

            // 其他活动列表
            foreach ($promotions as $promo) {
                $result['promotions'][] = $this->formatPromotion($promo);
            }

            return $this->success($result);
        } catch (\Exception $e) {
            return $this->error(lang('Operation failed'));
        }
    }

    /**
     * 格式化活动数据
     */
    private function formatPromotion(PromotionModel $promotion): array
    {
        // 获取活动商品数量
        $goodsCount = PromotionGoods::where('promotion_id', $promotion->id)->count();

        return [
            'id' => $promotion->id,
            'name' => $promotion->getTranslated('name', $this->locale),
            'type' => (int) $promotion->type,
            'banner' => UrlHelper::getFullUrl($promotion->banner ?? ''),
            'description' => $promotion->getTranslated('description', $this->locale),
            'rules' => $promotion->rules,
            'startTime' => $promotion->start_time,
            'endTime' => $promotion->end_time,
            'status' => (int) $promotion->status,
            'sort' => (int) $promotion->sort,
            'goodsCount' => $goodsCount,
            'createdAt' => $promotion->created_at,
        ];
    }
}
