<?php
declare(strict_types=1);

namespace app\api\controller;

use think\Response;
use think\facade\Db;
use app\common\model\Goods as GoodsModel;
use app\common\model\GoodsTranslation;
use app\common\model\UserFavorite;
use app\common\model\BrowseHistory;
use app\common\model\SystemConfig;
use app\common\model\CustomerService;
use app\common\model\Promotion;
use app\common\model\PromotionGoods;
use app\common\model\SearchKeyword;
use app\common\model\GoodsConditionValue;
use app\common\model\CategoryConditionGroup;
use app\common\model\CategoryConditionGroupTranslation;
use app\common\model\CategoryConditionOption;
use app\common\model\CategoryConditionOptionTranslation;
use app\api\validate\GoodsValidate;
use app\common\model\GoodsDraft;
use app\common\helper\UrlHelper;

/**
 * 商品控制器
 */
class Goods extends Base
{
    /**
     * 商品列表
     * @return Response
     */
    public function index(): Response
    {
        [$page, $pageSize] = $this->getPageParams();

        $query = GoodsModel::where('status', GoodsModel::STATUS_ON_SALE);

        // 分类筛选
        $categoryId = input('categoryId');
        if ($categoryId) {
            $query->where('category_id', $categoryId);
        }

        // 关键词搜索
        // 注意：需要在所有语言中搜索，因为有些商品可能只有特定语言的翻译
        $keyword = input('keyword');
        if ($keyword) {
            // 在翻译表中搜索（所有语言）
            $goodsIds = GoodsTranslation::where('title|description', 'like', "%{$keyword}%")
                ->column('goods_id');

            if (!empty($goodsIds)) {
                $query->whereIn('id', array_unique($goodsIds));
            } else {
                // 没有匹配结果
                return $this->paginate([], 0, $page, $pageSize);
            }
        }

        // 价格筛选
        $minPrice = input('minPrice');
        $maxPrice = input('maxPrice');
        if ($minPrice !== null) {
            $query->where('price', '>=', $minPrice);
        }
        if ($maxPrice !== null) {
            $query->where('price', '<=', $maxPrice);
        }

        // 成色筛选
        $condition = input('condition');
        if ($condition) {
            $query->where('condition', $condition);
        }

        // 类型筛选
        $type = input('type');
        if ($type) {
            $query->where('type', $type);
        }

        // 热门筛选
        $isHot = input('is_hot');
        if ($isHot !== null && $isHot !== '') {
            $query->where('is_hot', (int)$isHot);
        }

        // 推荐筛选
        $isRecommend = input('is_recommend');
        if ($isRecommend !== null && $isRecommend !== '') {
            $query->where('is_recommend', (int)$isRecommend);
        }

        // 店铺筛选
        $shopId = input('shopId');
        if ($shopId) {
            $query->where('shop_id', $shopId);
        }

        // 用户筛选
        $userId = input('userId');
        if ($userId) {
            $query->where('user_id', $userId);
        }

        // 品牌筛选（通过商品规格specs中的brand字段）
        // 注意：需要在所有语言中查找，因为有些商品可能只有特定语言的翻译
        $brand = input('brand');
        if ($brand) {
            $brandGoodsIds = GoodsTranslation::whereRaw("JSON_UNQUOTE(JSON_EXTRACT(specs, '$.brand')) = ?", [$brand])
                ->column('goods_id');

            if (!empty($brandGoodsIds)) {
                $query->whereIn('id', array_unique($brandGoodsIds));
            } else {
                return $this->paginate([], 0, $page, $pageSize);
            }
        }

        // 型号/系列筛选（通过商品规格specs中的model字段）
        // 注意：需要在所有语言中查找，因为有些商品可能只有特定语言的翻译
        // 使用不区分大小写的匹配，并将下划线和空格视为等效
        $model = input('model');
        if ($model) {
            // 将下划线替换为空格，用于匹配不同格式的 model 值
            $modelNormalized = str_replace('_', ' ', strtolower($model));
            $modelWithUnderscore = str_replace(' ', '_', strtolower($model));

            $modelGoodsIds = GoodsTranslation::whereRaw(
                "LOWER(REPLACE(JSON_UNQUOTE(JSON_EXTRACT(specs, '$.model')), '_', ' ')) = ? " .
                "OR LOWER(REPLACE(JSON_UNQUOTE(JSON_EXTRACT(specs, '$.model')), ' ', '_')) = ? " .
                "OR LOWER(REPLACE(JSON_UNQUOTE(JSON_EXTRACT(specs, '$.series')), '_', ' ')) = ? " .
                "OR LOWER(REPLACE(JSON_UNQUOTE(JSON_EXTRACT(specs, '$.series')), ' ', '_')) = ? " .
                "OR LOWER(REPLACE(JSON_UNQUOTE(JSON_EXTRACT(specs, '$.Series')), '_', ' ')) = ? " .
                "OR LOWER(REPLACE(JSON_UNQUOTE(JSON_EXTRACT(specs, '$.Series')), ' ', '_')) = ?",
                [$modelNormalized, $modelWithUnderscore, $modelNormalized, $modelWithUnderscore, $modelNormalized, $modelWithUnderscore]
            )->column('goods_id');

            if (!empty($modelGoodsIds)) {
                $query->whereIn('id', array_unique($modelGoodsIds));
            } else {
                return $this->paginate([], 0, $page, $pageSize);
            }
        }

        // 排序
        $sort = input('sort', 'newest');
        switch ($sort) {
            case 'price_asc':
                $query->order('price', 'asc');
                break;
            case 'price_desc':
                $query->order('price', 'desc');
                break;
            case 'popular':
                // 综合热度：收藏*3 + 浏览量，避免同品类扎堆
                $query->orderRaw('(likes * 3 + views) DESC, published_at DESC');
                break;
            case 'newest':
            default:
                $query->order('published_at', 'desc');
                break;
        }

        // 分页
        $total = $query->count();

        // 记录搜索关键词（仅首页请求时记录）
        if ($keyword && $page === 1) {
            SearchKeyword::recordKeyword($keyword, $total, $this->locale);
        }

        $goodsList = $query->with(['user'])
            ->page($page, $pageSize)
            ->select();

        // 转换为 API 格式（驼峰命名）
        $list = [];
        foreach ($goodsList as $goods) {
            $item = $goods->toApiArray($this->locale);
            // 添加用户信息
            if ($goods->user) {
                $item['user'] = [
                    'id' => $goods->user->id,
                    'uuid' => $goods->user->uuid,
                    'nickname' => $goods->user->nickname,
                    'avatar' => UrlHelper::convertImageUrl($goods->user->avatar),
                ];
            }
            $list[] = $item;
        }

        // 检查是否收藏
        if ($this->userId) {
            $likedIds = UserFavorite::where('user_id', $this->userId)
                ->whereIn('goods_id', array_column($list, 'id'))
                ->column('goods_id');

            foreach ($list as &$item) {
                $item['isLiked'] = in_array($item['id'], $likedIds);
            }
        }

        // 批量查询活动价格信息
        $goodsIds = array_column($list, 'id');
        $promotionMap = $this->getBatchGoodsPromotionInfo($goodsIds);
        foreach ($list as &$item) {
            if (isset($promotionMap[$item['id']])) {
                $item['promotion'] = $promotionMap[$item['id']];
            }
        }

        return $this->paginate($list, $total, $page, $pageSize);
    }

    /**
     * 商品详情
     * @param int $id
     * @return Response
     */
    public function read(int $id): Response
    {
        $goods = GoodsModel::with(['user', 'category'])->find($id);

        if (!$goods) {
            return $this->error('Goods not found', 404);
        }

        // 增加浏览量
        $goods->incrementViews();

        // 记录浏览历史
        if ($this->userId) {
            $this->recordBrowseHistory($goods->id);
        }

        // 获取商品数据
        $data = $goods->toApiArray($this->locale);

        // 添加关联信息
        // 只有 user_id=0 时使用虚拟卖家（客服），其他使用真实卖家
        if ($goods->user_id === 0) {
            // 平台商品：获取随机在线客服作为虚拟卖家
            $service = CustomerService::getRandomOnlineService($this->locale);
            if ($service) {
                $data['seller'] = [
                    'id' => 0, // 平台商品卖家ID为0
                    'uuid' => 'service_' . $service['id'], // 客服标识
                    'nickname' => $service['name'],
                    'avatar' => UrlHelper::convertImageUrl($service['avatar']),
                    'goodsCount' => GoodsModel::where('user_id', 0)
                        ->where('status', GoodsModel::STATUS_ON_SALE)
                        ->count(),
                    'rating' => 99.5,
                    'isService' => true, // 标记为客服
                    'serviceId' => $service['id'], // 客服ID，用于聊天
                    'isOnline' => $service['isOnline'],
                    'isVerified' => true,
                ];
            }
        } elseif ($goods->user) {
            // C2C 商品：使用真实卖家信息
            $goodsCount = GoodsModel::where('user_id', $goods->user->id)
                ->where('status', GoodsModel::STATUS_ON_SALE)
                ->count();

            $data['seller'] = [
                'id' => $goods->user->id,
                'uuid' => $goods->user->uuid,
                'nickname' => $goods->user->nickname,
                'avatar' => UrlHelper::convertImageUrl($goods->user->avatar),
                'goodsCount' => $goodsCount,
                'rating' => 98.9, // TODO: 从用户评价系统获取真实评分
                'isService' => false,
                'isVerified' => (bool) $goods->user->is_verified,
            ];
        }

        if ($goods->category) {
            $data['category'] = [
                'id' => $goods->category->id,
                'name' => $goods->category->getTranslated('name', $this->locale),
            ];
        }

        // 检查是否收藏
        if ($this->userId) {
            $data['isLiked'] = UserFavorite::where('user_id', $this->userId)
                ->where('goods_id', $id)
                ->find() !== null;
        } else {
            $data['isLiked'] = false;
        }

        // 获取系统配置的快递设置（支持多语言）
        $expressDelivery = SystemConfig::getConfigTranslated('express_delivery', $this->locale, '');
        $data['expressDelivery'] = $expressDelivery;

        // 查询该商品是否参与进行中的活动
        $promotionInfo = $this->getGoodsPromotionInfo($id);
        if ($promotionInfo) {
            $data['promotion'] = $promotionInfo;
        }

        // 获取商品状态配置值
        $data['conditionValues'] = $this->getGoodsConditionValues($id, $this->locale);

        return $this->success($data);
    }

    /**
     * 获取商品的状态配置值（含翻译）
     * @param int $goodsId
     * @param string $locale
     * @return array
     */
    protected function getGoodsConditionValues(int $goodsId, string $locale): array
    {
        // 获取商品已保存的状态配置
        $savedValues = GoodsConditionValue::where('goods_id', $goodsId)
            ->select()
            ->toArray();

        if (empty($savedValues)) {
            return [];
        }

        // 获取所有相关的组ID和选项ID
        $groupIds = array_unique(array_column($savedValues, 'group_id'));
        $optionIds = array_unique(array_column($savedValues, 'option_id'));

        // 获取状态组信息
        $groups = CategoryConditionGroup::whereIn('id', $groupIds)
            ->where('status', 1)
            ->column('*', 'id');

        // 获取状态组翻译
        $groupTranslations = CategoryConditionGroupTranslation::whereIn('group_id', $groupIds)
            ->select()
            ->toArray();
        $groupTransMap = [];
        foreach ($groupTranslations as $t) {
            $groupTransMap[$t['group_id']][$t['locale']] = $t['name'];
        }

        // 获取选项信息
        $options = CategoryConditionOption::whereIn('id', $optionIds)
            ->where('status', 1)
            ->column('*', 'id');

        // 获取选项翻译
        $optionTranslations = CategoryConditionOptionTranslation::whereIn('option_id', $optionIds)
            ->select()
            ->toArray();
        $optionTransMap = [];
        foreach ($optionTranslations as $t) {
            $optionTransMap[$t['option_id']][$t['locale']] = $t['name'];
        }

        // 组装结果
        $result = [];
        foreach ($savedValues as $sv) {
            $groupId = $sv['group_id'];
            $optionId = $sv['option_id'];

            if (!isset($groups[$groupId]) || !isset($options[$optionId])) {
                continue;
            }

            $group = $groups[$groupId];
            $option = $options[$optionId];

            // 获取翻译名称，按优先级：当前语言 -> zh-tw -> en-us -> 原始名称
            $groupName = $groupTransMap[$groupId][$locale]
                ?? $groupTransMap[$groupId]['zh-tw']
                ?? $groupTransMap[$groupId]['en-us']
                ?? $group['name'];

            $optionName = $optionTransMap[$optionId][$locale]
                ?? $optionTransMap[$optionId]['zh-tw']
                ?? $optionTransMap[$optionId]['en-us']
                ?? $option['name'];

            $result[] = [
                'groupId' => $groupId,
                'groupName' => $groupName,
                'groupIcon' => $group['icon'],
                'optionId' => $optionId,
                'optionName' => $optionName,
                'impactLevel' => $option['impact_level'],
                'sort' => $group['sort'],
            ];
        }

        // 按组排序
        usort($result, function ($a, $b) {
            return $a['sort'] - $b['sort'];
        });

        return $result;
    }

    /**
     * 批量获取商品的活动信息
     * @param array $goodsIds
     * @return array 以 goods_id 为 key 的活动信息数组
     */
    protected function getBatchGoodsPromotionInfo(array $goodsIds): array
    {
        if (empty($goodsIds)) {
            return [];
        }

        $now = date('Y-m-d H:i:s');

        // 查找这些商品参与的进行中的活动
        $promotionGoodsList = PromotionGoods::alias('pg')
            ->join('promotions p', 'pg.promotion_id = p.id')
            ->whereIn('pg.goods_id', $goodsIds)
            ->where('p.status', Promotion::STATUS_RUNNING)
            ->where('p.start_time', '<=', $now)
            ->where('p.end_time', '>=', $now)
            ->field('pg.*, p.type as promotion_type, p.start_time, p.end_time, p.id as promotion_id')
            ->select();

        if ($promotionGoodsList->isEmpty()) {
            return [];
        }

        // 获取所有相关活动的翻译名称
        $promotionIds = array_unique($promotionGoodsList->column('promotion_id'));
        $promotions = Promotion::whereIn('id', $promotionIds)->select()->column(null, 'id');

        $result = [];
        foreach ($promotionGoodsList as $pg) {
            $promotion = $promotions[$pg['promotion_id']] ?? null;
            $promotionName = $promotion ? $promotion->getTranslated('name', $this->locale) : '';

            $result[$pg['goods_id']] = [
                'id' => (int) $pg['promotion_id'],
                'name' => $promotionName,
                'type' => (int) $pg['promotion_type'],
                'promotionPrice' => (float) $pg['promotion_price'],
                'discount' => (float) $pg['discount'],
                'discountPercent' => round((1 - (float) $pg['discount']) * 100),
                'endTime' => $pg['end_time'],
            ];
        }

        return $result;
    }

    /**
     * 获取商品的活动信息
     * @param int $goodsId
     * @return array|null
     */
    protected function getGoodsPromotionInfo(int $goodsId): ?array
    {
        // 查找该商品参与的进行中的活动
        $now = date('Y-m-d H:i:s');

        $promotionGoods = PromotionGoods::alias('pg')
            ->join('promotions p', 'pg.promotion_id = p.id')
            ->where('pg.goods_id', $goodsId)
            ->where('p.status', Promotion::STATUS_RUNNING)
            ->where('p.start_time', '<=', $now)
            ->where('p.end_time', '>=', $now)
            ->field('pg.*, p.type as promotion_type, p.start_time, p.end_time, p.id as promotion_id')
            ->find();

        if (!$promotionGoods) {
            return null;
        }

        // 获取活动的翻译名称
        $promotion = Promotion::find($promotionGoods['promotion_id']);
        $promotionName = $promotion ? $promotion->getTranslated('name', $this->locale) : '';

        return [
            'id' => (int) $promotionGoods['promotion_id'],
            'name' => $promotionName,
            'type' => (int) $promotionGoods['promotion_type'],
            'promotionPrice' => (float) $promotionGoods['promotion_price'],
            'discount' => (float) $promotionGoods['discount'],
            'discountPercent' => round((1 - (float) $promotionGoods['discount']) * 100), // 折扣百分比，如 20 表示 8折
            'stock' => (int) $promotionGoods['stock'],
            'soldCount' => (int) $promotionGoods['sold_count'],
            'limitPerUser' => (int) $promotionGoods['limit_per_user'],
            'startTime' => $promotionGoods['start_time'],
            'endTime' => $promotionGoods['end_time'],
        ];
    }

    /**
     * AI 生成商品描述
     * @return Response
     */
    public function generateDescription(): Response
    {
        $title = input('post.title', '');
        $brand = input('post.brand', '');
        $model = input('post.model', '');
        $condition = input('post.condition', '');
        $specs = input('post.specs', []);

        if (empty($title)) {
            return $this->error('Title is required');
        }

        // 从 specs 中提取额外信息
        $storage = $specs['storage'] ?? $specs['硬碟容量'] ?? '';
        $color = $specs['color'] ?? $specs['顏色'] ?? '';
        if (empty($brand) && !empty($specs['brand'])) {
            $brand = $specs['brand'];
        }
        if (empty($model) && !empty($specs['model'])) {
            $model = $specs['model'];
        }

        $productInfo = [
            'title' => $title,
            'brand' => $brand,
            'model' => $model,
            'storage' => $storage,
            'color' => $color,
            'condition_text' => $condition,
        ];

        // 优先 DeepSeek，其次 Gemini，传入当前用户语言
        $descriptions = [];
        $deepseek = new \app\common\service\DeepSeekService();
        if ($deepseek->isConfigured()) {
            try {
                $descriptions = $deepseek->generateProductDescription($productInfo, $this->locale);
            } catch (\Exception $e) {
                // fallback to gemini
            }
        }

        if (empty($descriptions) || empty($descriptions[$this->locale] ?? '')) {
            $gemini = new \app\common\service\GeminiService();
            if ($gemini->isConfigured()) {
                try {
                    $descriptions = $gemini->generateProductDescription($productInfo, $this->locale);
                } catch (\Exception $e) {
                    // ignore
                }
            }
        }

        if (empty($descriptions) || empty($descriptions[$this->locale] ?? '')) {
            return $this->error('AI service unavailable');
        }

        // 返回当前语言的描述
        return $this->success([
            'description' => $descriptions[$this->locale] ?? $descriptions['en-us'] ?? '',
            'descriptions' => $descriptions,
        ]);
    }

    /**
     * 发布商品
     * @return Response
     */
    public function create(): Response
    {
        $data = input('post.');

        // 验证参数
        try {
            $this->validate($data, GoodsValidate::class, 'create');
        } catch (\Exception $e) {
            return $this->error($e->getMessage());
        }

        Db::startTrans();
        try {
            // 创建商品
            $goods = new GoodsModel();
            $goods->goods_no = $this->generateNo('G');
            $goods->user_id = $this->userId;
            $goods->shop_id = $this->user->shop->id ?? null;
            $goods->category_id = $data['categoryId'];
            $goods->type = $this->user->isSeller() ? GoodsModel::TYPE_B2C : GoodsModel::TYPE_C2C;
            $goods->condition = $data['condition'] ?? 1;
            $goods->price = $data['price'];
            $goods->original_price = $data['originalPrice'] ?? null;
            $goods->currency = $data['currency'] ?? 'USD';
            $goods->stock = $data['stock'] ?? 1;
            $goods->images = $data['images'];
            $goods->video = $data['video'] ?? null;
            $goods->location_country = $data['locationCountry'] ?? null;
            $goods->location_city = $data['locationCity'] ?? null;
            $goods->shipping_fee = $data['shippingFee'] ?? 0;
            $goods->free_shipping = !empty($data['freeShipping']) ? 1 : 0;
            $goods->is_negotiable = !empty($data['isNegotiable']) ? 1 : 0;
            $goods->status = GoodsModel::STATUS_PENDING;
            $goods->save();

            // 保存翻译（包含 specs）
            $translationData = [
                'title' => $data['title'],
                'description' => $data['description'] ?? '',
            ];
            if (!empty($data['specs'])) {
                $translationData['specs'] = $data['specs'];
            }
            $goods->saveTranslation($this->locale, $translationData, true, false);

            // 保存商品状态值
            if (!empty($data['conditionValues'])) {
                GoodsConditionValue::saveGoodsConditions($goods->id, $data['conditionValues']);
            }

            // 用户发布商品后自动成为卖家
            if (!$this->user->is_seller) {
                $this->user->is_seller = 1;
                $this->user->save();
            }

            Db::commit();

            return $this->success($goods->toApiArray($this->locale), 'Goods created');

        } catch (\Exception $e) {
            Db::rollback();
            return $this->error('Create goods failed: ' . $e->getMessage());
        }
    }

    /**
     * 更新商品
     * @param int $id
     * @return Response
     */
    public function update(int $id): Response
    {
        $goods = GoodsModel::where('id', $id)
            ->where('user_id', $this->userId)
            ->find();

        if (!$goods) {
            return $this->error('Goods not found', 404);
        }

        $data = input('post.');

        Db::startTrans();
        try {
            // 更新商品基本信息
            $allowFields = [
                'categoryId' => 'category_id',
                'condition' => 'condition',
                'price' => 'price',
                'originalPrice' => 'original_price',
                'stock' => 'stock',
                'images' => 'images',
                'video' => 'video',
                'locationCountry' => 'location_country',
                'locationCity' => 'location_city',
                'shippingFee' => 'shipping_fee',
                'freeShipping' => 'free_shipping',
                'isNegotiable' => 'is_negotiable',
            ];

            foreach ($allowFields as $inputKey => $dbKey) {
                if (isset($data[$inputKey])) {
                    $value = $data[$inputKey];
                    if (in_array($dbKey, ['free_shipping', 'is_negotiable'])) {
                        $value = $value ? 1 : 0;
                    }
                    $goods->$dbKey = $value;
                }
            }
            $goods->save();

            // 更新翻译
            if (isset($data['title']) || isset($data['description'])) {
                $goods->saveTranslation($this->locale, [
                    'title' => $data['title'] ?? $goods->getTranslated('title', $this->locale),
                    'description' => $data['description'] ?? $goods->getTranslated('description', $this->locale),
                ]);
            }

            Db::commit();

            return $this->success($goods->toApiArray($this->locale), 'Goods updated');

        } catch (\Exception $e) {
            Db::rollback();
            return $this->error('Update goods failed');
        }
    }

    /**
     * 删除商品
     * @param int $id
     * @return Response
     */
    public function delete(int $id): Response
    {
        $goods = GoodsModel::where('id', $id)
            ->where('user_id', $this->userId)
            ->find();

        if (!$goods) {
            return $this->error('Goods not found', 404);
        }

        try {
            $goods->delete();
            return $this->success([], 'Goods deleted');
        } catch (\Exception $e) {
            return $this->error('Delete goods failed');
        }
    }

    /**
     * 收藏/取消收藏
     * @param int $id
     * @return Response
     */
    public function like(int $id): Response
    {
        $goods = GoodsModel::find($id);

        if (!$goods) {
            return $this->error('Goods not found', 404);
        }

        $favorite = UserFavorite::where('user_id', $this->userId)
            ->where('goods_id', $id)
            ->find();

        Db::startTrans();
        try {
            if ($favorite) {
                // 取消收藏
                $favorite->delete();
                $goods->incrementLikes(-1);
                $isLiked = false;
            } else {
                // 添加收藏
                $newFavorite = new UserFavorite();
                $newFavorite->user_id = $this->userId;
                $newFavorite->goods_id = $id;
                $newFavorite->save();
                $goods->incrementLikes(1);
                $isLiked = true;
            }

            Db::commit();

            return $this->success(['isLiked' => $isLiked]);

        } catch (\Exception $e) {
            Db::rollback();
            return $this->error('Operation failed');
        }
    }

    /**
     * 记录浏览历史
     * @param int $goodsId
     */
    protected function recordBrowseHistory(int $goodsId): void
    {
        try {
            $history = BrowseHistory::where('user_id', $this->userId)
                ->where('goods_id', $goodsId)
                ->find();

            if ($history) {
                $history->view_count = $history->view_count + 1;
                $history->last_view_at = date('Y-m-d H:i:s');
                $history->save();
            } else {
                $history = new BrowseHistory();
                $history->user_id = $this->userId;
                $history->goods_id = $goodsId;
                $history->save();
            }
        } catch (\Exception $e) {
            // 忽略错误
        }
    }

    /**
     * 热门搜索词
     * @return Response
     */
    public function hotSearches(): Response
    {
        $limit = input('limit', 10);
        $days = input('days', 7);

        $keywords = SearchKeyword::getHotKeywords((int) $limit, (int) $days, $this->locale);

        return $this->success($keywords);
    }

    /**
     * 搜索建议
     * @return Response
     */
    public function suggestions(): Response
    {
        $keyword = input('keyword', '');
        $limit = input('limit', 10);

        if (empty($keyword)) {
            return $this->success([]);
        }

        $suggestions = SearchKeyword::getSuggestions($keyword, (int) $limit, $this->locale);

        return $this->success($suggestions);
    }

    /**
     * 相似商品推荐
     * 基于同分类 + 相似价格区间（±30%）
     * @return Response
     */
    public function similar(): Response
    {
        $goodsId = input('id', 0, 'intval');
        $limit = input('limit', 10, 'intval');

        if (!$goodsId) {
            return $this->success([]);
        }

        $goods = GoodsModel::find($goodsId);
        if (!$goods) {
            return $this->success([]);
        }

        // 同分类 + 相似价格区间 (±30%)
        $minPrice = $goods->price * 0.7;
        $maxPrice = $goods->price * 1.3;

        $query = GoodsModel::where('category_id', $goods->category_id)
            ->where('id', '<>', $goodsId)
            ->where('status', GoodsModel::STATUS_ON_SALE)
            ->whereBetween('price', [$minPrice, $maxPrice])
            ->order('views', 'desc')
            ->limit($limit);

        $list = $query->select();

        // 如果同分类商品不够，补充热门商品
        if ($list->count() < $limit) {
            $existingIds = $list->column('id');
            $existingIds[] = $goodsId;

            $moreGoods = GoodsModel::whereNotIn('id', $existingIds)
                ->where('status', GoodsModel::STATUS_ON_SALE)
                ->order('views', 'desc')
                ->limit($limit - $list->count())
                ->select();

            $list = $list->merge($moreGoods);
        }

        return $this->success($this->formatGoodsList($list));
    }

    /**
     * 用户也看了（协同过滤）
     * 基于浏览过当前商品的用户还浏览过的其他商品
     * @return Response
     */
    public function alsoViewed(): Response
    {
        $goodsId = input('id', 0, 'intval');
        $limit = input('limit', 10, 'intval');

        if (!$goodsId) {
            return $this->success([]);
        }

        // 查找浏览过当前商品的用户（最近100个）
        $userIds = BrowseHistory::where('goods_id', $goodsId)
            ->order('last_view_at', 'desc')
            ->limit(100)
            ->column('user_id');

        if (empty($userIds)) {
            // 无浏览数据时返回热门商品
            return $this->getHotGoods($goodsId, $limit);
        }

        // 查找这些用户还浏览过的其他商品（按浏览次数排序）
        $goodsIds = BrowseHistory::whereIn('user_id', $userIds)
            ->where('goods_id', '<>', $goodsId)
            ->group('goods_id')
            ->orderRaw('COUNT(*) DESC')
            ->limit($limit * 2) // 多取一些，后面过滤
            ->column('goods_id');

        if (empty($goodsIds)) {
            return $this->getHotGoods($goodsId, $limit);
        }

        // 获取商品详情（只取在售的）
        $list = GoodsModel::whereIn('id', $goodsIds)
            ->where('status', GoodsModel::STATUS_ON_SALE)
            ->limit($limit)
            ->select();

        // 如果商品不够，补充热门商品
        if ($list->count() < $limit) {
            $existingIds = $list->column('id');
            $existingIds[] = $goodsId;

            $moreGoods = GoodsModel::whereNotIn('id', $existingIds)
                ->where('status', GoodsModel::STATUS_ON_SALE)
                ->order('views', 'desc')
                ->limit($limit - $list->count())
                ->select();

            $list = $list->merge($moreGoods);
        }

        return $this->success($this->formatGoodsList($list));
    }

    /**
     * 个性化推荐
     * 基于用户收藏和浏览历史推荐
     * @return Response
     */
    public function recommendations(): Response
    {
        $excludeId = input('exclude', 0, 'intval');
        $limit = input('limit', 10, 'intval');

        // 未登录用户：返回热门商品
        if (!$this->userId) {
            return $this->getHotGoods($excludeId, $limit);
        }

        // 已登录用户：基于收藏和浏览历史推荐
        // 1. 获取用户感兴趣的分类（从收藏中获取）
        $categoryIds = UserFavorite::alias('uf')
            ->join('goods g', 'uf.goods_id = g.id')
            ->where('uf.user_id', $this->userId)
            ->group('g.category_id')
            ->column('g.category_id');

        // 2. 如果没有收藏，从浏览历史获取
        if (empty($categoryIds)) {
            $rows = BrowseHistory::alias('bh')
                ->join('goods g', 'bh.goods_id = g.id')
                ->where('bh.user_id', $this->userId)
                ->group('g.category_id')
                ->field('g.category_id, COUNT(*) as cnt')
                ->order('cnt', 'desc')
                ->limit(5)
                ->select();
            $categoryIds = $rows->column('category_id');
        }

        // 3. 如果还是没有，返回热门商品
        if (empty($categoryIds)) {
            return $this->getHotGoods($excludeId, $limit);
        }

        // 4. 获取用户已浏览/已收藏的商品ID（排除这些）
        $viewedIds = BrowseHistory::where('user_id', $this->userId)
            ->column('goods_id');
        $likedIds = UserFavorite::where('user_id', $this->userId)
            ->column('goods_id');
        $excludeIds = array_unique(array_merge($viewedIds, $likedIds));
        if ($excludeId) {
            $excludeIds[] = $excludeId;
        }

        // 5. 从感兴趣的分类中推荐新商品
        $query = GoodsModel::whereIn('category_id', $categoryIds)
            ->where('status', GoodsModel::STATUS_ON_SALE);

        if (!empty($excludeIds)) {
            $query->whereNotIn('id', $excludeIds);
        }

        $list = $query->order('views', 'desc')
            ->limit($limit)
            ->select();

        // 如果商品不够，补充热门商品（允许已浏览的商品）
        if ($list->count() < $limit) {
            $existingIds = $list->column('id');
            // 只排除当前商品和已经在列表中的商品
            $supplementExcludeIds = $existingIds;
            if ($excludeId) {
                $supplementExcludeIds[] = $excludeId;
            }

            $moreGoods = GoodsModel::whereNotIn('id', $supplementExcludeIds)
                ->where('status', GoodsModel::STATUS_ON_SALE)
                ->order('views', 'desc')
                ->limit($limit - $list->count())
                ->select();

            $list = $list->merge($moreGoods);
        }

        return $this->success($this->formatGoodsList($list));
    }

    /**
     * 获取热门商品（辅助方法）
     * @param int $excludeId 排除的商品ID
     * @param int $limit 数量限制
     * @return Response
     */
    protected function getHotGoods(int $excludeId, int $limit): Response
    {
        $query = GoodsModel::where('status', GoodsModel::STATUS_ON_SALE)
            ->order('views', 'desc')
            ->limit($limit);

        if ($excludeId) {
            $query->where('id', '<>', $excludeId);
        }

        $list = $query->select();

        return $this->success($this->formatGoodsList($list));
    }

    /**
     * 格式化商品列表（用于推荐API）
     * @param \think\Collection $goodsList
     * @return array
     */
    protected function formatGoodsList($goodsList): array
    {
        $list = [];
        foreach ($goodsList as $goods) {
            $item = $goods->toApiArray($this->locale);
            $list[] = $item;
        }

        // 检查是否收藏
        if ($this->userId && !empty($list)) {
            $likedIds = UserFavorite::where('user_id', $this->userId)
                ->whereIn('goods_id', array_column($list, 'id'))
                ->column('goods_id');

            foreach ($list as &$item) {
                $item['isLiked'] = in_array($item['id'], $likedIds);
            }
        }

        // 批量查询活动价格信息
        if (!empty($list)) {
            $goodsIds = array_column($list, 'id');
            $promotionMap = $this->getBatchGoodsPromotionInfo($goodsIds);
            foreach ($list as &$item) {
                if (isset($promotionMap[$item['id']])) {
                    $item['promotion'] = $promotionMap[$item['id']];
                }
            }
        }

        return $list;
    }

    /**
     * 保存草稿
     * @return Response
     */
    public function saveDraft(): Response
    {
        $data = [
            'category_id' => input('post.categoryId'),
            'title' => input('post.title', ''),
            'description' => input('post.description', ''),
            'images' => input('post.images', []),
            'price' => input('post.price'),
            'condition_level' => input('post.condition'),
            'is_negotiable' => input('post.isNegotiable', false),
            'free_shipping' => input('post.freeShipping', true),
            'shipping_fee' => input('post.shippingFee'),
            'specs' => input('post.specs', []),
            'condition_values' => input('post.conditionValues', []),
            'current_step' => input('post.currentStep', 1),
        ];

        try {
            $draft = GoodsDraft::saveDraft($this->userId, $data);
            return $this->success([
                'id' => $draft->id,
                'updated_at' => $draft->updated_at,
            ], 'Draft saved');
        } catch (\Exception $e) {
            // 记录错误日志
            \think\facade\Log::error('Save draft failed: ' . $e->getMessage() . "\n" . $e->getTraceAsString());
            return $this->error('Save draft failed: ' . $e->getMessage());
        }
    }

    /**
     * 获取草稿
     * @return Response
     */
    public function getDraft(): Response
    {
        $draft = GoodsDraft::getUserDraft($this->userId);

        if (!$draft) {
            return $this->success(null);
        }

        return $this->success($draft->toApiArray($this->locale));
    }

    /**
     * 删除草稿
     * @return Response
     */
    public function deleteDraft(): Response
    {
        try {
            GoodsDraft::deleteDraft($this->userId);
            return $this->success([], 'Draft deleted');
        } catch (\Exception $e) {
            return $this->error('Delete draft failed');
        }
    }

    /**
     * 获取当前用户的商品列表
     * @return Response
     */
    public function myGoods(): Response
    {
        // 确保用户已登录且 userId 是有效的正整数
        $userId = (int) $this->userId;
        if ($userId <= 0) {
            return $this->error('Please login first', 401);
        }

        [$page, $pageSize] = $this->getPageParams();

        // 明确只查询当前用户的商品，排除 user_id=0 的平台商品
        $query = GoodsModel::where('user_id', '=', $userId);

        // 状态筛选
        $status = input('status', 'all');
        switch ($status) {
            case 'active': // 出品中/上架中
                $query->where('status', GoodsModel::STATUS_ON_SALE);
                break;
            case 'pending': // 待审核
                $query->where('status', GoodsModel::STATUS_PENDING);
                break;
            case 'sold': // 已售出
                $query->where('status', GoodsModel::STATUS_SOLD_OUT);
                break;
            case 'off': // 已下架
                $query->where('status', GoodsModel::STATUS_OFF_SHELF);
                break;
            case 'all':
            default:
                // 全部（不含违规）
                $query->where('status', '<>', GoodsModel::STATUS_REJECTED);
                break;
        }

        // 排序：默认按更新时间倒序
        $sort = input('sort', 'newest');
        switch ($sort) {
            case 'price_asc':
                $query->order('price', 'asc');
                break;
            case 'price_desc':
                $query->order('price', 'desc');
                break;
            case 'views':
                $query->order('views', 'desc');
                break;
            case 'newest':
            default:
                $query->order('updated_at', 'desc');
                break;
        }

        // 分页
        $total = $query->count();
        $goodsList = $query->page($page, $pageSize)->select();

        $list = [];
        foreach ($goodsList as $goods) {
            $item = $goods->toApiArray($this->locale);
            // 添加状态文字
            $item['statusText'] = $this->getStatusText($goods->status);
            $list[] = $item;
        }

        // 批量查询活动价格信息
        if (!empty($list)) {
            $goodsIds = array_column($list, 'id');
            $promotionMap = $this->getBatchGoodsPromotionInfo($goodsIds);
            foreach ($list as &$item) {
                if (isset($promotionMap[$item['id']])) {
                    $item['promotion'] = $promotionMap[$item['id']];
                }
            }
        }

        return $this->paginate($list, $total, $page, $pageSize);
    }

    /**
     * 获取当前用户商品统计数据
     * @return Response
     */
    public function myGoodsStats(): Response
    {
        // 确保用户已登录且 userId 是有效的正整数
        $userId = (int) $this->userId;
        if ($userId <= 0) {
            return $this->error('Please login first', 401);
        }

        $stats = [
            'active' => GoodsModel::where('user_id', $userId)
                ->where('status', GoodsModel::STATUS_ON_SALE)
                ->count(),
            'pending' => GoodsModel::where('user_id', $userId)
                ->where('status', GoodsModel::STATUS_PENDING)
                ->count(),
            'sold' => GoodsModel::where('user_id', $userId)
                ->where('status', GoodsModel::STATUS_SOLD_OUT)
                ->count(),
            'off' => GoodsModel::where('user_id', $userId)
                ->where('status', GoodsModel::STATUS_OFF_SHELF)
                ->count(),
        ];
        $stats['total'] = $stats['active'] + $stats['pending'] + $stats['sold'] + $stats['off'];

        // 获取草稿数量
        $stats['draft'] = GoodsDraft::where('user_id', $userId)->count();

        return $this->success($stats);
    }

    /**
     * 获取状态文字
     * @param int $status
     * @return string
     */
    protected function getStatusText(int $status): string
    {
        $statusMap = [
            GoodsModel::STATUS_PENDING => 'pending',
            GoodsModel::STATUS_ON_SALE => 'active',
            GoodsModel::STATUS_OFF_SHELF => 'off',
            GoodsModel::STATUS_SOLD_OUT => 'sold',
            GoodsModel::STATUS_REJECTED => 'rejected',
        ];
        return $statusMap[$status] ?? 'unknown';
    }

    /**
     * 下架商品
     * @param int $id
     * @return Response
     */
    public function offShelf(int $id): Response
    {
        $goods = GoodsModel::where('id', $id)
            ->where('user_id', $this->userId)
            ->find();

        if (!$goods) {
            return $this->error('Goods not found', 404);
        }

        // 只有上架状态的商品可以下架
        if ($goods->status !== GoodsModel::STATUS_ON_SALE) {
            return $this->error('Only active goods can be off shelf');
        }

        try {
            $goods->status = GoodsModel::STATUS_OFF_SHELF;
            $goods->save();
            return $this->success(['status' => 'off'], 'Goods off shelf success');
        } catch (\Exception $e) {
            return $this->error('Off shelf failed');
        }
    }

    /**
     * 上架商品
     * @param int $id
     * @return Response
     */
    public function onShelf(int $id): Response
    {
        $goods = GoodsModel::where('id', $id)
            ->where('user_id', $this->userId)
            ->find();

        if (!$goods) {
            return $this->error('Goods not found', 404);
        }

        // 只有下架状态的商品可以上架
        if ($goods->status !== GoodsModel::STATUS_OFF_SHELF) {
            return $this->error('Only off shelf goods can be on shelf');
        }

        try {
            $goods->status = GoodsModel::STATUS_ON_SALE;
            $goods->published_at = date('Y-m-d H:i:s');
            $goods->save();
            return $this->success(['status' => 'active'], 'Goods on shelf success');
        } catch (\Exception $e) {
            return $this->error('On shelf failed');
        }
    }

    /**
     * 获取型号/系列统计数据
     * @return Response
     */
    public function modelStats(): Response
    {
        $model = input('model');
        $categoryId = input('categoryId');

        if (!$model) {
            return $this->error('Model is required');
        }

        // 将下划线替换为空格，用于匹配不同格式的 model 值
        $modelNormalized = str_replace('_', ' ', strtolower($model));
        $modelWithUnderscore = str_replace(' ', '_', strtolower($model));

        // 查询匹配该型号的商品 ID（同时查 model、series、Series）
        $query = GoodsTranslation::whereRaw(
            "LOWER(REPLACE(JSON_UNQUOTE(JSON_EXTRACT(specs, '$.model')), '_', ' ')) = ? " .
            "OR LOWER(REPLACE(JSON_UNQUOTE(JSON_EXTRACT(specs, '$.model')), ' ', '_')) = ? " .
            "OR LOWER(REPLACE(JSON_UNQUOTE(JSON_EXTRACT(specs, '$.series')), '_', ' ')) = ? " .
            "OR LOWER(REPLACE(JSON_UNQUOTE(JSON_EXTRACT(specs, '$.series')), ' ', '_')) = ? " .
            "OR LOWER(REPLACE(JSON_UNQUOTE(JSON_EXTRACT(specs, '$.Series')), '_', ' ')) = ? " .
            "OR LOWER(REPLACE(JSON_UNQUOTE(JSON_EXTRACT(specs, '$.Series')), ' ', '_')) = ?",
            [$modelNormalized, $modelWithUnderscore, $modelNormalized, $modelWithUnderscore, $modelNormalized, $modelWithUnderscore]
        );

        $goodsIds = $query->column('goods_id');
        $goodsIds = array_unique($goodsIds);

        if (empty($goodsIds)) {
            return $this->success([
                'goodsCount' => 0,
                'totalViews' => 0,
                'minPrice' => null,
                'maxPrice' => null,
            ]);
        }

        // 查询在售商品的统计数据
        $statsQuery = GoodsModel::whereIn('id', $goodsIds)
            ->where('status', GoodsModel::STATUS_ON_SALE);

        if ($categoryId) {
            $statsQuery->where('category_id', $categoryId);
        }

        $stats = $statsQuery->field([
            'COUNT(*) as goods_count',
            'SUM(views) as total_views',
            'MIN(price) as min_price',
            'MAX(price) as max_price',
        ])->find();

        return $this->success([
            'goodsCount' => (int) ($stats['goods_count'] ?? 0),
            'totalViews' => (int) ($stats['total_views'] ?? 0),
            'minPrice' => $stats['min_price'] ? (float) $stats['min_price'] : null,
            'maxPrice' => $stats['max_price'] ? (float) $stats['max_price'] : null,
        ]);
    }
}
