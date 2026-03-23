<?php
declare(strict_types=1);

namespace app\admin\controller;

use think\Response;
use app\common\model\Promotion as PromotionModel;
use app\common\model\PromotionGoods;
use app\common\model\Goods;

/**
 * 营销活动管理控制器
 */
class Promotion extends Base
{
    /**
     * 支持的语言列表
     */
    protected $supportedLocales = ['zh-tw', 'en-us', 'ja-jp'];

    /**
     * 活动列表
     */
    public function index(): Response
    {
        [$page, $pageSize] = $this->getPageParams();
        $locale = input('locale', 'zh-tw');

        $query = PromotionModel::order('id', 'desc');

        $keyword = input('keyword', '');
        if ($keyword) {
            $query->where('name', 'like', "%{$keyword}%");
        }

        $type = input('type', '');
        if ($type !== '') {
            $query->where('type', (int)$type);
        }

        $status = input('status', '');
        if ($status !== '') {
            $query->where('status', (int)$status);
        }

        $total = $query->count();
        $list = $query->page($page, $pageSize)->select()->toArray();

        // 附加翻译
        $list = PromotionModel::appendTranslations($list, $locale);

        return $this->paginate($list, $total, $page, $pageSize);
    }

    /**
     * 活动详情
     */
    public function read(int $id): Response
    {
        $promotion = PromotionModel::with(['goods'])->find($id);
        if (!$promotion) {
            return $this->error('活动不存在', 404);
        }

        $data = $promotion->toArray();
        // 获取所有翻译
        $data['translations'] = [];
        foreach ($this->supportedLocales as $locale) {
            $translation = $promotion->translation($locale);
            $data['translations'][$locale] = $translation ? $translation->toArray() : [
                'name' => '',
                'description' => ''
            ];
        }

        return $this->success($data);
    }

    /**
     * 创建活动
     */
    public function create(): Response
    {
        $data = input('post.');
        $translations = $data['translations'] ?? [];

        // 验证至少有一种语言的名称
        $hasName = false;
        foreach ($translations as $locale => $trans) {
            if (!empty($trans['name'])) {
                $hasName = true;
                break;
            }
        }
        if (!$hasName && empty($data['name'])) {
            return $this->error('请填写活动名称');
        }

        $promotion = new PromotionModel();
        $promotion->name = $data['name'] ?? ($translations['zh-tw']['name'] ?? '');
        $promotion->type = $data['type'] ?? 1;
        $promotion->banner = $data['banner'] ?? null;
        $promotion->description = $data['description'] ?? ($translations['zh-tw']['description'] ?? null);
        $promotion->rules = $data['rules'] ?? null;
        $promotion->start_time = $data['start_time'];
        $promotion->end_time = $data['end_time'];
        $promotion->status = $data['status'] ?? 0;
        $promotion->sort = $data['sort'] ?? 0;
        $promotion->save();

        // 保存翻译
        foreach ($this->supportedLocales as $locale) {
            if (isset($translations[$locale])) {
                $promotion->saveTranslation($locale, $translations[$locale]);
            }
        }

        return $this->success(['id' => $promotion->id], '创建成功');
    }

    /**
     * 更新活动
     */
    public function update(int $id): Response
    {
        $promotion = PromotionModel::find($id);
        if (!$promotion) {
            return $this->error('活动不存在', 404);
        }

        $data = input('post.');
        $translations = $data['translations'] ?? [];

        $allowFields = ['name', 'type', 'banner', 'description', 'rules',
                        'start_time', 'end_time', 'status', 'sort'];

        foreach ($allowFields as $field) {
            if (isset($data[$field])) {
                $promotion->$field = $data[$field];
            }
        }
        $promotion->save();

        // 更新翻译
        foreach ($this->supportedLocales as $locale) {
            if (isset($translations[$locale])) {
                $promotion->saveTranslation($locale, $translations[$locale]);
            }
        }

        return $this->success([], '更新成功');
    }

    /**
     * 删除活动
     */
    public function delete(int $id): Response
    {
        $promotion = PromotionModel::find($id);
        if (!$promotion) {
            return $this->error('活动不存在', 404);
        }

        $promotion->delete();
        return $this->success([], '删除成功');
    }

    /**
     * 开始活动
     */
    public function start(int $id): Response
    {
        $promotion = PromotionModel::find($id);
        if (!$promotion) {
            return $this->error('活动不存在', 404);
        }

        $promotion->status = PromotionModel::STATUS_RUNNING;
        $promotion->save();

        return $this->success([], '活动已开始');
    }

    /**
     * 结束活动
     */
    public function stop(int $id): Response
    {
        $promotion = PromotionModel::find($id);
        if (!$promotion) {
            return $this->error('活动不存在', 404);
        }

        $promotion->status = PromotionModel::STATUS_ENDED;
        $promotion->save();

        return $this->success([], '活动已结束');
    }

    /**
     * 获取活动商品列表
     */
    public function goodsList(int $id): Response
    {
        $promotion = PromotionModel::find($id);
        if (!$promotion) {
            return $this->error('活动不存在', 404);
        }

        [$page, $pageSize] = $this->getPageParams();
        $locale = input('locale', 'zh-tw');

        $query = PromotionGoods::where('promotion_id', $id)
            ->with(['goods']);

        $total = $query->count();
        $list = $query->page($page, $pageSize)->select();

        $result = [];
        foreach ($list as $item) {
            if ($item->goods) {
                $result[] = [
                    'id' => $item->id,
                    'goods_id' => $item->goods_id,
                    'promotion_price' => (float) $item->promotion_price,
                    'discount' => (float) $item->discount,
                    'stock' => (int) $item->stock,
                    'sold_count' => (int) $item->sold_count,
                    'limit_per_user' => (int) $item->limit_per_user,
                    'created_at' => $item->created_at,
                    'goods' => [
                        'id' => $item->goods->id,
                        'goods_no' => $item->goods->goods_no,
                        'title' => $item->goods->getTranslated('title', $locale),
                        'price' => (float) $item->goods->price,
                        'images' => $item->goods->images,
                        'stock' => (int) $item->goods->stock,
                        'status' => (int) $item->goods->status,
                    ],
                ];
            }
        }

        return $this->paginate($result, $total, $page, $pageSize);
    }

    /**
     * 添加活动商品
     */
    public function addGoods(int $id): Response
    {
        $promotion = PromotionModel::find($id);
        if (!$promotion) {
            return $this->error('活动不存在', 404);
        }

        $goodsId = input('post.goods_id');
        if (empty($goodsId)) {
            return $this->error('请选择商品');
        }

        $goods = Goods::find($goodsId);
        if (!$goods) {
            return $this->error('商品不存在');
        }

        // 检查是否已添加
        $exists = PromotionGoods::where('promotion_id', $id)
            ->where('goods_id', $goodsId)
            ->find();
        if ($exists) {
            return $this->error('该商品已在活动中');
        }

        $promotionPrice = input('post.promotion_price', $goods->price);
        $originalPrice = (float) $goods->price;

        // 计算折扣率
        $discount = $originalPrice > 0 ? round($promotionPrice / $originalPrice, 2) : 1;

        $promotionGoods = new PromotionGoods();
        $promotionGoods->promotion_id = $id;
        $promotionGoods->goods_id = $goodsId;
        $promotionGoods->promotion_price = $promotionPrice;
        $promotionGoods->discount = $discount;
        $promotionGoods->stock = input('post.stock', $goods->stock);
        $promotionGoods->sold_count = 0;
        $promotionGoods->limit_per_user = input('post.limit_per_user', 0);
        $promotionGoods->save();

        return $this->success(['id' => $promotionGoods->id], '添加成功');
    }

    /**
     * 更新活动商品
     */
    public function updateGoods(int $id, int $goodsId): Response
    {
        $promotionGoods = PromotionGoods::where('promotion_id', $id)
            ->where('id', $goodsId)
            ->find();

        if (!$promotionGoods) {
            return $this->error('活动商品不存在', 404);
        }

        $goods = Goods::find($promotionGoods->goods_id);
        $originalPrice = $goods ? (float) $goods->price : 0;

        $promotionPrice = input('post.promotion_price');
        if ($promotionPrice !== null) {
            $promotionGoods->promotion_price = $promotionPrice;
            // 重新计算折扣率
            $promotionGoods->discount = $originalPrice > 0 ? round($promotionPrice / $originalPrice, 2) : 1;
        }

        if (input('post.stock') !== null) {
            $promotionGoods->stock = input('post.stock');
        }

        if (input('post.limit_per_user') !== null) {
            $promotionGoods->limit_per_user = input('post.limit_per_user');
        }

        $promotionGoods->save();

        return $this->success([], '更新成功');
    }

    /**
     * 删除活动商品
     */
    public function removeGoods(int $id, int $goodsId): Response
    {
        $promotionGoods = PromotionGoods::where('promotion_id', $id)
            ->where('id', $goodsId)
            ->find();

        if (!$promotionGoods) {
            return $this->error('活动商品不存在', 404);
        }

        $promotionGoods->delete();

        return $this->success([], '删除成功');
    }

    /**
     * 搜索可添加的商品
     */
    public function searchGoods(int $id): Response
    {
        $keyword = input('get.keyword', '');
        $page = max(1, (int) input('get.page', 1));
        $pageSize = min(100, max(1, (int) input('get.pageSize', 20)));
        $locale = input('locale', 'zh-tw');

        // 筛选条件
        $categoryId = input('get.category_id', '');
        $brandId = input('get.brand_id', '');
        $goodsType = input('get.goods_type', ''); // 商品类型: 1=个人闲置, 2=全新商品

        // 获取已添加的商品ID
        $addedIds = PromotionGoods::where('promotion_id', $id)->column('goods_id');

        $query = Goods::where('status', 1); // 只查询上架商品

        // 排除已添加的商品
        if (!empty($addedIds)) {
            $query->whereNotIn('id', $addedIds);
        }

        // 关键词搜索
        if ($keyword) {
            $query->where(function ($q) use ($keyword) {
                $q->where('goods_no', 'like', "%{$keyword}%")
                    ->whereOr('title', 'like', "%{$keyword}%");
            });
        }

        // 分类筛选
        if ($categoryId !== '') {
            $query->where('category_id', (int) $categoryId);
        }

        // 品牌筛选
        if ($brandId !== '') {
            $query->where('brand_id', (int) $brandId);
        }

        // 商品类型筛选
        if ($goodsType !== '') {
            $query->where('type', (int) $goodsType);
        }

        $total = $query->count();
        $list = $query->order('id', 'desc')
            ->page($page, $pageSize)
            ->select();

        $result = [];
        foreach ($list as $item) {
            $result[] = [
                'id' => $item->id,
                'goods_no' => $item->goods_no,
                'title' => $item->getTranslated('title', $locale),
                'price' => (float) $item->price,
                'images' => $item->images,
                'stock' => (int) $item->stock,
                'category_id' => (int) $item->category_id,
                'brand_id' => (int) ($item->brand_id ?? 0),
                'goods_type' => (int) ($item->type ?? 1),
            ];
        }

        return $this->paginate($result, $total, $page, $pageSize);
    }

    /**
     * 批量添加活动商品
     */
    public function batchAddGoods(int $id): Response
    {
        $promotion = PromotionModel::find($id);
        if (!$promotion) {
            return $this->error('活动不存在', 404);
        }

        $goodsIds = input('post.goods_ids', []);
        $discountRate = input('post.discount_rate', 1); // 折扣率，例如 0.8 表示8折

        if (empty($goodsIds) || !is_array($goodsIds)) {
            return $this->error('请选择商品');
        }

        // 获取已添加的商品ID
        $addedIds = PromotionGoods::where('promotion_id', $id)->column('goods_id');

        $successCount = 0;
        $skipCount = 0;

        foreach ($goodsIds as $goodsId) {
            // 跳过已添加的商品
            if (in_array($goodsId, $addedIds)) {
                $skipCount++;
                continue;
            }

            $goods = Goods::find($goodsId);
            if (!$goods) {
                continue;
            }

            $originalPrice = (float) $goods->price;
            $promotionPrice = round($originalPrice * $discountRate, 2);

            $promotionGoods = new PromotionGoods();
            $promotionGoods->promotion_id = $id;
            $promotionGoods->goods_id = $goodsId;
            $promotionGoods->promotion_price = $promotionPrice;
            $promotionGoods->discount = $discountRate;
            $promotionGoods->stock = $goods->stock;
            $promotionGoods->sold_count = 0;
            $promotionGoods->limit_per_user = 0;
            $promotionGoods->save();

            $successCount++;
        }

        return $this->success([
            'success_count' => $successCount,
            'skip_count' => $skipCount,
        ], "成功添加 {$successCount} 个商品" . ($skipCount > 0 ? "，跳过 {$skipCount} 个已存在商品" : ''));
    }

    /**
     * 批量更新活动商品折扣
     */
    public function batchUpdateGoods(int $id): Response
    {
        $promotion = PromotionModel::find($id);
        if (!$promotion) {
            return $this->error('活动不存在', 404);
        }

        $goodsIds = input('post.goods_ids', []); // promotion_goods表的id列表
        $discount = input('post.discount', 1); // 折扣率，1-10代表1折到10折

        if (empty($goodsIds) || !is_array($goodsIds)) {
            return $this->error('请选择商品');
        }

        if ($discount < 1 || $discount > 10) {
            return $this->error('折扣必须在1到10之间');
        }

        // 将1-10折转换为0.1-1的折扣率
        $discountRate = $discount / 10;

        $successCount = 0;

        foreach ($goodsIds as $goodsId) {
            $promotionGoods = PromotionGoods::where('promotion_id', $id)
                ->where('id', $goodsId)
                ->find();

            if (!$promotionGoods) {
                continue;
            }

            // 获取原始商品价格
            $goods = Goods::find($promotionGoods->goods_id);
            if (!$goods) {
                continue;
            }

            $originalPrice = (float) $goods->price;
            $promotionPrice = round($originalPrice * $discountRate, 2);

            $promotionGoods->promotion_price = $promotionPrice;
            $promotionGoods->discount = $discountRate;
            $promotionGoods->save();

            $successCount++;
        }

        return $this->success([
            'success_count' => $successCount,
        ], "成功更新 {$successCount} 个商品的折扣");
    }
}
