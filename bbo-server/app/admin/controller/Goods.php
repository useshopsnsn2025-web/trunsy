<?php
declare(strict_types=1);

namespace app\admin\controller;

use think\Response;
use app\common\model\Goods as GoodsModel;
use app\common\model\GoodsTranslation;
use app\common\model\GoodsConditionValue;
use app\common\model\User;
use app\common\model\Country;
use app\common\model\SystemConfig;
use app\common\service\NotificationService;
use app\common\helper\UrlHelper;

/**
 * 商品管理控制器
 */
class Goods extends Base
{
    /**
     * 状态映射
     */
    const STATUS_MAP = [
        0 => '待审核',
        1 => '已上架',
        2 => '已下架',
        3 => '已售罄',
        4 => '违规下架',
    ];

    /**
     * 成色映射
     */
    const CONDITION_MAP = [
        1 => '全新',
        2 => '几乎全新',
        3 => '轻微使用痕迹',
        4 => '明显使用痕迹',
        5 => '有缺陷/故障',
    ];

    /**
     * 商品列表
     * @return Response
     */
    public function index(): Response
    {
        [$page, $pageSize] = $this->getPageParams();

        $query = GoodsModel::with(['user', 'category'])
            ->order('id', 'desc');

        // 搜索条件
        $keyword = input('keyword', '');
        if ($keyword) {
            if (ctype_digit($keyword)) {
                // 纯数字：仅精确匹配商品ID
                $query->where('id', (int) $keyword);
            } else {
                // 非数字：匹配标题和编号
                $ids = GoodsTranslation::where('title', 'like', "%{$keyword}%")
                    ->column('goods_id');
                if ($ids) {
                    $query->where(function($q) use ($ids, $keyword) {
                        $q->whereIn('id', $ids)
                            ->whereOr('goods_no', 'like', "%{$keyword}%");
                    });
                } else {
                    $query->where('goods_no', 'like', "%{$keyword}%");
                }
            }
        }

        $categoryId = input('category_id', '');
        if ($categoryId !== '') {
            $query->where('category_id', (int) $categoryId);
        }

        $status = input('status', '');
        if ($status !== '') {
            $query->where('status', (int) $status);
        }

        $userId = input('user_id', '');
        if ($userId !== '') {
            $query->where('user_id', (int) $userId);
        }

        // 浏览量筛选
        $viewsMin = input('views_min', '');
        if ($viewsMin !== '') {
            $query->where('views', '>=', (int) $viewsMin);
        }
        $viewsMax = input('views_max', '');
        if ($viewsMax !== '') {
            $query->where('views', '<=', (int) $viewsMax);
        }

        // 收藏数筛选
        $likesMin = input('likes_min', '');
        if ($likesMin !== '') {
            $query->where('likes', '>=', (int) $likesMin);
        }
        $likesMax = input('likes_max', '');
        if ($likesMax !== '') {
            $query->where('likes', '<=', (int) $likesMax);
        }

        // 品牌筛选（从 en-us 翻译的 specs.brand 字段匹配）
        $brand = input('brand', '');
        if ($brand) {
            $brandIds = GoodsTranslation::where('locale', 'en-us')
                ->whereRaw("LOWER(JSON_UNQUOTE(JSON_EXTRACT(specs, '$.brand'))) LIKE ?", ["%" . strtolower($brand) . "%"])
                ->column('goods_id');
            if ($brandIds) {
                $query->whereIn('id', $brandIds);
            } else {
                $query->where('id', 0);
            }
        }

        $total = $query->count();
        $list = $query->page($page, $pageSize)->select()->toArray();

        // 获取翻译
        if ($list) {
            $ids = array_column($list, 'id');
            $translations = GoodsTranslation::whereIn('goods_id', $ids)
                ->select()
                ->toArray();

            $translationMap = [];
            foreach ($translations as $t) {
                $translationMap[$t['goods_id']][$t['locale']] = $t;
            }

            foreach ($list as &$item) {
                $item['translations'] = $translationMap[$item['id']] ?? [];
                $item['status_text'] = self::STATUS_MAP[$item['status']] ?? '未知';
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
            return $this->error('商品不存在', 404);
        }

        $data = $goods->toArray();

        // 获取翻译
        $translations = GoodsTranslation::where('goods_id', $id)
            ->select()
            ->toArray();

        $data['translations'] = [];
        foreach ($translations as $t) {
            $data['translations'][$t['locale']] = $t;
        }

        $data['status_text'] = self::STATUS_MAP[$data['status']] ?? '未知';

        // 获取商品状态配置值
        $conditionValues = GoodsConditionValue::where('goods_id', $id)
            ->select()
            ->toArray();
        $data['condition_values'] = $conditionValues;

        return $this->success($data);
    }

    /**
     * 更新商品
     * @param int $id
     * @return Response
     */
    public function update(int $id): Response
    {
        $goods = GoodsModel::find($id);
        if (!$goods) {
            return $this->error('商品不存在', 404);
        }

        $data = input('post.');

        // 更新基本信息
        $allowFields = [
            'category_id', 'type', 'condition', 'price', 'original_price',
            'currency', 'stock', 'images', 'video',
            'location_country', 'location_city',
            'shipping_fee', 'free_shipping', 'is_negotiable', 'status'
        ];

        foreach ($allowFields as $field) {
            if (isset($data[$field])) {
                $goods->$field = $data[$field];
            }
        }
        $goods->save();

        // 更新翻译
        if (!empty($data['translations']) && is_array($data['translations'])) {
            foreach ($data['translations'] as $locale => $trans) {
                $translation = GoodsTranslation::where('goods_id', $id)
                    ->where('locale', $locale)
                    ->find();

                if (!$translation) {
                    $translation = new GoodsTranslation();
                    $translation->goods_id = $id;
                    $translation->locale = $locale;
                }

                if (isset($trans['title'])) {
                    $translation->title = $trans['title'];
                }
                if (isset($trans['description'])) {
                    $translation->description = $trans['description'];
                }
                if (isset($trans['specs'])) {
                    $translation->specs = $trans['specs'];
                }
                $translation->save();
            }
        }

        // 保存商品状态配置值
        if (isset($data['condition_values']) && is_array($data['condition_values'])) {
            // 先删除旧的配置
            GoodsConditionValue::where('goods_id', $id)->delete();

            // 插入新的配置
            foreach ($data['condition_values'] as $cv) {
                if (!empty($cv['group_id']) && !empty($cv['option_id'])) {
                    $conditionValue = new GoodsConditionValue();
                    $conditionValue->goods_id = $id;
                    $conditionValue->group_id = (int) $cv['group_id'];
                    $conditionValue->option_id = (int) $cv['option_id'];
                    $conditionValue->save();
                }
            }
        }

        return $this->success([], '更新成功');
    }

    /**
     * 删除商品（软删除）
     * @param int $id
     * @return Response
     */
    public function delete(int $id): Response
    {
        $goods = GoodsModel::find($id);
        if (!$goods) {
            return $this->error('商品不存在', 404);
        }

        // 删除关联数据
        GoodsTranslation::where('goods_id', $id)->delete();
        GoodsConditionValue::where('goods_id', $id)->delete();

        $goods->delete();

        return $this->success([], '删除成功');
    }

    /**
     * 上架商品
     * @param int $id
     * @return Response
     */
    public function online(int $id): Response
    {
        $goods = GoodsModel::find($id);
        if (!$goods) {
            return $this->error('商品不存在', 404);
        }

        $goods->status = GoodsModel::STATUS_ON_SALE;
        $goods->save();

        return $this->success([], '上架成功');
    }

    /**
     * 下架商品
     * @param int $id
     * @return Response
     */
    public function offline(int $id): Response
    {
        $goods = GoodsModel::find($id);
        if (!$goods) {
            return $this->error('商品不存在', 404);
        }

        $goods->status = GoodsModel::STATUS_OFF_SHELF;
        $goods->save();

        return $this->success([], '下架成功');
    }

    /**
     * 审核通过
     * @param int $id
     * @return Response
     */
    public function approve(int $id): Response
    {
        $goods = GoodsModel::find($id);
        if (!$goods) {
            return $this->error('商品不存在', 404);
        }

        if ($goods->status !== GoodsModel::STATUS_PENDING) {
            return $this->error('商品状态不正确');
        }

        $goods->status = GoodsModel::STATUS_ON_SALE;
        $goods->published_at = date('Y-m-d H:i:s');
        $goods->save();

        // 发送审核通过通知给卖家
        try {
            $notificationService = new NotificationService();
            $notificationService->notifyGoodsApproved($goods->user_id, [
                'id' => $goods->id,
                'title' => $goods->getTranslated('title'),
                'goods_no' => $goods->goods_no,
            ]);
        } catch (\Exception $e) {
            // 通知发送失败不影响审核结果
            \think\facade\Log::error('Failed to send goods approval notification: ' . $e->getMessage());
        }

        return $this->success([], '审核通过');
    }

    /**
     * 审核拒绝
     * @param int $id
     * @return Response
     */
    public function reject(int $id): Response
    {
        $goods = GoodsModel::find($id);
        if (!$goods) {
            return $this->error('商品不存在', 404);
        }

        $reason = input('post.reason', '');
        $goods->status = GoodsModel::STATUS_REJECTED;
        $goods->reject_reason = $reason;
        $goods->save();

        // 发送审核拒绝通知给卖家
        try {
            $notificationService = new NotificationService();
            $notificationService->notifyGoodsRejected($goods->user_id, [
                'id' => $goods->id,
                'title' => $goods->getTranslated('title'),
                'goods_no' => $goods->goods_no,
                'reject_reason' => $reason,
            ]);
        } catch (\Exception $e) {
            // 通知发送失败不影响审核结果
            \think\facade\Log::error('Failed to send goods rejection notification: ' . $e->getMessage());
        }

        return $this->success([], '已拒绝');
    }

    /**
     * 创建商品
     * @return Response
     */
    public function create(): Response
    {
        $data = input('post.');

        // 验证必填字段
        if (empty($data['user_id'])) {
            return $this->error('请选择发布用户');
        }
        if (empty($data['category_id'])) {
            return $this->error('请选择商品分类');
        }
        if (empty($data['translations']) || !is_array($data['translations'])) {
            return $this->error('请填写商品标题');
        }

        // 检查用户是否存在
        $user = User::find($data['user_id']);
        if (!$user) {
            return $this->error('用户不存在');
        }

        // 生成商品编号
        $goodsNo = 'G' . date('YmdHis') . str_pad((string) mt_rand(1, 9999), 4, '0', STR_PAD_LEFT);

        // 创建商品
        $goods = new GoodsModel();
        $goods->goods_no = $goodsNo;
        $goods->user_id = $data['user_id'];
        $goods->category_id = $data['category_id'];
        $goods->type = $data['type'] ?? 1;
        $goods->condition = $data['condition'] ?? 1;
        $goods->price = $data['price'] ?? 0;
        $goods->original_price = $data['original_price'] ?? 0;
        $goods->currency = $data['currency'] ?? 'USD';
        $goods->stock = $data['stock'] ?? 1;
        $goods->images = $data['images'] ?? [];
        $goods->video = $data['video'] ?? '';
        $goods->location_country = $data['location_country'] ?? '';
        $goods->location_city = $data['location_city'] ?? '';
        $goods->shipping_fee = $data['shipping_fee'] ?? 0;
        $goods->free_shipping = $data['free_shipping'] ?? 0;
        $goods->is_negotiable = $data['is_negotiable'] ?? 0;
        $goods->likes = mt_rand(10, 50); // 初始收藏数随机 10-50
        $goods->status = $data['status'] ?? GoodsModel::STATUS_ON_SALE;
        $goods->save();

        // 保存翻译
        foreach ($data['translations'] as $locale => $trans) {
            if (!empty($trans['title'])) {
                $translation = new GoodsTranslation();
                $translation->goods_id = $goods->id;
                $translation->locale = $locale;
                $translation->title = $trans['title'];
                $translation->description = $trans['description'] ?? '';
                $translation->specs = $trans['specs'] ?? null;
                $translation->is_original = $locale === ($data['original_locale'] ?? 'zh-cn') ? 1 : 0;
                $translation->save();
            }
        }

        // 保存商品状态配置值
        if (!empty($data['condition_values']) && is_array($data['condition_values'])) {
            foreach ($data['condition_values'] as $cv) {
                if (!empty($cv['group_id']) && !empty($cv['option_id'])) {
                    $conditionValue = new GoodsConditionValue();
                    $conditionValue->goods_id = $goods->id;
                    $conditionValue->group_id = (int) $cv['group_id'];
                    $conditionValue->option_id = (int) $cv['option_id'];
                    $conditionValue->save();
                }
            }
        }

        return $this->success(['id' => $goods->id, 'goods_no' => $goodsNo], '创建成功');
    }

    /**
     * 商品统计
     * @return Response
     */
    public function statistics(): Response
    {
        // 总商品数
        $totalGoods = GoodsModel::count();

        // 各状态商品数
        $statusCounts = [];
        foreach (self::STATUS_MAP as $status => $text) {
            $statusCounts[] = [
                'status' => $status,
                'text' => $text,
                'count' => GoodsModel::where('status', $status)->count()
            ];
        }

        // 今日新增
        $today = date('Y-m-d');
        $todayGoods = GoodsModel::whereDay('created_at', $today)->count();

        // 本月新增
        $monthGoods = GoodsModel::whereMonth('created_at')->count();

        // 待审核数量
        $pendingCount = GoodsModel::where('status', GoodsModel::STATUS_PENDING)->count();

        return $this->success([
            'total_goods' => $totalGoods,
            'status_counts' => $statusCounts,
            'today_goods' => $todayGoods,
            'month_goods' => $monthGoods,
            'pending_count' => $pendingCount,
        ]);
    }

    /**
     * 批量审核
     * @return Response
     */
    public function batchApprove(): Response
    {
        $ids = input('post.ids', []);
        if (empty($ids) || !is_array($ids)) {
            return $this->error('请选择要审核的商品');
        }

        // 先获取待审核的商品ID和user_id（轻量查询）
        $pendingGoods = GoodsModel::whereIn('id', $ids)
            ->where('status', GoodsModel::STATUS_PENDING)
            ->field('id, user_id, goods_no')
            ->select()
            ->toArray();

        $count = GoodsModel::whereIn('id', $ids)
            ->where('status', GoodsModel::STATUS_PENDING)
            ->update([
                'status' => GoodsModel::STATUS_ON_SALE,
                'published_at' => date('Y-m-d H:i:s'),
            ]);

        // 发送审核通知（超过10个时跳过逐条通知，避免阻塞）
        if ($count > 0 && $count <= 10 && !empty($pendingGoods)) {
            $goodsIds = array_column($pendingGoods, 'id');
            $titles = GoodsTranslation::whereIn('goods_id', $goodsIds)
                ->where('locale', 'en-us')
                ->column('title', 'goods_id');

            $notificationService = new NotificationService();
            foreach ($pendingGoods as $goods) {
                try {
                    $notificationService->notifyGoodsApproved($goods['user_id'], [
                        'id' => $goods['id'],
                        'title' => $titles[$goods['id']] ?? '',
                        'goods_no' => $goods['goods_no'],
                    ], [NotificationService::CHANNEL_MESSAGE]);
                } catch (\Exception $e) {
                    \think\facade\Log::error('Failed to send goods approval notification: ' . $e->getMessage());
                }
            }
        }

        return $this->success(['count' => $count], "成功审核 {$count} 个商品");
    }

    /**
     * 批量下架
     * @return Response
     */
    public function batchOffline(): Response
    {
        $ids = input('post.ids', []);
        if (empty($ids) || !is_array($ids)) {
            return $this->error('请选择要下架的商品');
        }

        $count = GoodsModel::whereIn('id', $ids)
            ->where('status', GoodsModel::STATUS_ON_SALE)
            ->update(['status' => GoodsModel::STATUS_OFF_SHELF]);

        return $this->success(['count' => $count], "成功下架 {$count} 个商品");
    }

    /**
     * 批量删除
     * @return Response
     */
    public function batchDelete(): Response
    {
        $ids = input('post.ids', []);
        if (empty($ids) || !is_array($ids)) {
            return $this->error('请选择要删除的商品');
        }

        // 删除关联数据
        GoodsTranslation::whereIn('goods_id', $ids)->delete();
        GoodsConditionValue::whereIn('goods_id', $ids)->delete();

        $count = GoodsModel::whereIn('id', $ids)->delete();

        return $this->success(['count' => $count], "成功删除 {$count} 个商品");
    }

    /**
     * 批量设置热门
     * @return Response
     */
    public function batchSetHot(): Response
    {
        $ids = input('post.ids', []);
        $isHot = input('post.is_hot', 1);
        if (empty($ids) || !is_array($ids)) {
            return $this->error('Please select products');
        }

        $count = GoodsModel::whereIn('id', $ids)
            ->update(['is_hot' => (int)$isHot]);

        $action = $isHot ? '设为热门' : '取消热门';
        return $this->success(['count' => $count], "成功{$action} {$count} 个商品");
    }

    /**
     * 批量设置推荐
     * @return Response
     */
    public function batchSetRecommend(): Response
    {
        $ids = input('post.ids', []);
        $isRecommend = input('post.is_recommend', 1);
        if (empty($ids) || !is_array($ids)) {
            return $this->error('Please select products');
        }

        $count = GoodsModel::whereIn('id', $ids)
            ->update(['is_recommend' => (int)$isRecommend]);

        $action = $isRecommend ? '设为推荐' : '取消推荐';
        return $this->success(['count' => $count], "成功{$action} {$count} 个商品");
    }

    /**
     * 单个商品切换热门状态
     * @param int $id
     * @return Response
     */
    public function toggleHot(int $id): Response
    {
        $goods = GoodsModel::find($id);
        if (!$goods) {
            return $this->error('Product not found');
        }

        $goods->is_hot = $goods->is_hot ? 0 : 1;
        $goods->save();

        return $this->success(['is_hot' => $goods->is_hot]);
    }

    /**
     * 单个商品切换推荐状态
     * @param int $id
     * @return Response
     */
    public function toggleRecommend(int $id): Response
    {
        $goods = GoodsModel::find($id);
        if (!$goods) {
            return $this->error('Product not found');
        }

        $goods->is_recommend = $goods->is_recommend ? 0 : 1;
        $goods->save();

        return $this->success(['is_recommend' => $goods->is_recommend]);
    }

    /**
     * 更新商品浏览量和收藏数
     * @param int $id
     * @return Response
     */
    public function updateStats(int $id): Response
    {
        $goods = GoodsModel::find($id);
        if (!$goods) {
            return $this->error('Product not found', 404);
        }

        $views = input('post.views');
        $likes = input('post.likes');

        if ($views !== null) {
            $goods->views = max(0, (int)$views);
        }
        if ($likes !== null) {
            $goods->likes = max(0, (int)$likes);
        }

        $goods->save();

        return $this->success([
            'views' => $goods->views,
            'likes' => $goods->likes,
        ]);
    }

    /**
     * 批量修改价格
     * @return Response
     */
    public function batchUpdatePrice(): Response
    {
        $ids = input('post.ids', []);
        $mode = input('post.mode', 'fixed');     // fixed: 固定金额, percent: 百分比
        $action = input('post.action', 'add');   // add: 加价, reduce: 减价
        $value = floatval(input('post.value', 0));

        if (empty($ids) || !is_array($ids)) {
            return $this->error('Please select goods');
        }

        if ($value <= 0) {
            return $this->error('Adjustment value must be greater than 0');
        }

        $updated = 0;
        foreach ($ids as $id) {
            $goods = GoodsModel::find((int)$id);
            if (!$goods) continue;

            $oldPrice = (float) $goods->price;

            if ($mode === 'percent') {
                // 百分比调整
                $adjustment = round($oldPrice * $value / 100, 2);
            } else {
                // 固定金额调整
                $adjustment = $value;
            }

            $newPrice = $action === 'add'
                ? round($oldPrice + $adjustment, 2)
                : round($oldPrice - $adjustment, 2);

            // 价格不能低于 0
            $goods->price = max(0.01, $newPrice);
            $goods->save();
            $updated++;
        }

        return $this->success(['updated' => $updated], "Updated {$updated} goods");
    }

    /**
     * 获取用户列表（用于选择发布者）
     * @return Response
     */
    public function users(): Response
    {
        $keyword = input('keyword', '');
        $query = User::field('id,uuid,nickname,avatar,email,phone')
            ->where('status', 1)
            ->order('id', 'desc')
            ->limit(50);

        if ($keyword) {
            $query->where(function ($q) use ($keyword) {
                $q->where('nickname', 'like', "%{$keyword}%")
                    ->whereOr('email', 'like', "%{$keyword}%")
                    ->whereOr('phone', 'like', "%{$keyword}%");
            });
        }

        $list = $query->select()->toArray();

        return $this->success($list);
    }

    /**
     * Export goods data (start async task)
     * @return Response
     */
    public function exportData(): Response
    {
        $ids = input('ids', []);
        $countryCode = input('country_code', '');

        if (empty($ids) || !is_array($ids)) {
            return $this->error('Please select goods to export');
        }
        if (empty($countryCode)) {
            return $this->error('Please select a country');
        }

        // Get country info
        $country = Country::where('code', $countryCode)->where('is_active', 1)->find();
        if (!$country) {
            return $this->error('Country not found');
        }

        $locale = $country->locale ?: 'en-us';
        $currencyCode = $country->currency_code ?: 'USD';

        // Get exchange rate
        $rate = 1.0;
        if ($currencyCode !== 'USD') {
            $rateValue = SystemConfig::getConfig('rate_' . $currencyCode);
            if ($rateValue) {
                $rate = (float) $rateValue;
            }
        }

        // Generate task ID
        $taskId = uniqid('export_', true);

        $exportDir = runtime_path() . 'export';
        if (!is_dir($exportDir)) {
            mkdir($exportDir, 0755, true);
        }

        // 使用进度文件（而非 Cache）确保 Web 和 CLI 环境一致
        $progressFile = $exportDir . DIRECTORY_SEPARATOR . $taskId . '_progress.json';
        file_put_contents($progressFile, json_encode([
            'status' => 'processing',
            'total' => count($ids),
            'current' => 0,
            'message' => 'Starting export...',
            'file' => '',
        ]));

        // 同步执行导出（直接在当前请求中完成）
        set_time_limit(300);
        ignore_user_abort(true);

        try {
            $this->processExportTask($taskId, $ids, $locale, $currencyCode, $rate, $progressFile, $exportDir);
        } catch (\Throwable $e) {
            file_put_contents($progressFile, json_encode([
                'status' => 'failed',
                'total' => count($ids),
                'current' => 0,
                'message' => '导出失败: ' . $e->getMessage(),
                'file' => '',
            ]));
            return $this->error('Export failed: ' . $e->getMessage());
        }

        // 直接返回完成后的下载信息
        $progress = json_decode(file_get_contents($progressFile), true);
        return $this->success($progress);
    }

    /**
     * Check export progress
     * @return Response
     */
    public function exportProgress(): Response
    {
        $taskId = input('task_id', '');
        if (empty($taskId) || !preg_match('/^export_[\w.]+$/', $taskId)) {
            return $this->error('Invalid task ID');
        }

        $progressFile = runtime_path() . 'export' . DIRECTORY_SEPARATOR . $taskId . '_progress.json';
        if (!file_exists($progressFile)) {
            return $this->error('Task not found');
        }

        $progress = json_decode(file_get_contents($progressFile), true);
        if (!$progress) {
            return $this->error('Task not found');
        }

        return $this->success($progress);
    }

    /**
     * Download exported file
     * @return Response
     */
    public function exportDownload(): Response
    {
        $taskId = input('task_id', '');
        if (empty($taskId)) {
            return $this->error('Invalid task ID');
        }

        $progressFile = runtime_path() . 'export' . DIRECTORY_SEPARATOR . $taskId . '_progress.json';
        if (!file_exists($progressFile)) {
            return $this->error('Task not found');
        }

        $progress = json_decode(file_get_contents($progressFile), true);
        if (!$progress || $progress['status'] !== 'completed' || empty($progress['file'])) {
            return $this->error('File not ready');
        }

        $filePath = $progress['file'];
        if (!file_exists($filePath)) {
            return $this->error('File not found');
        }

        // 下载成功后清理（延迟清理，允许重试下载）
        // progress 文件保留，ZIP 下载完由前端关闭弹窗时清理

        // Return file download
        $fileName = 'goods_export_' . date('Ymd_His') . '.zip';
        return response(file_get_contents($filePath), 200, [
            'Content-Type' => 'application/zip',
            'Content-Disposition' => 'attachment; filename="' . $fileName . '"',
            'Content-Length' => filesize($filePath),
        ]);
    }

    /**
     * Find PHP 8 binary path
     */
    /**
     * 执行导出任务
     */
    public function processExportTask(string $taskId, array $ids, string $locale, string $currencyCode, float $rate, string $progressFile, string $exportDir): void
    {
        $total = count($ids);

        $tempDir = $exportDir . DIRECTORY_SEPARATOR . $taskId;
        if (!is_dir($tempDir)) {
            mkdir($tempDir, 0755, true);
        }

        $siteUrl = SystemConfig::getConfig('site_url', 'https://www.turnsysg.com');

        $currencyModel = \app\common\model\Currency::where('code', $currencyCode)->find();
        $decimals = $currencyModel ? (int) $currencyModel->decimals : 2;

        // 汇总表格数据
        $summaryRows = [];

        foreach ($ids as $index => $goodsId) {
            $goodsId = (int) $goodsId;

            file_put_contents($progressFile, json_encode([
                'status' => 'processing',
                'total' => $total,
                'current' => $index,
                'message' => "正在处理商品 {$goodsId} (" . ($index + 1) . "/{$total})",
                'file' => '',
            ]));

            $goods = GoodsModel::find($goodsId);
            if (!$goods) continue;

            $translation = GoodsTranslation::where('goods_id', $goodsId)->where('locale', $locale)->find();
            if (!$translation) {
                $translation = GoodsTranslation::where('goods_id', $goodsId)->where('locale', 'en-us')->find();
            }
            if (!$translation) continue;

            $goodsDir = $tempDir . DIRECTORY_SEPARATOR . $goodsId;
            if (!is_dir($goodsDir)) {
                mkdir($goodsDir, 0755, true);
            }

            $title = $translation->title ?: '';
            file_put_contents($goodsDir . DIRECTORY_SEPARATOR . '标题.txt', $title);

            // 去除 HTML 标签，保留纯文本
            $desc = $translation->description ?: '';
            $desc = preg_replace('/<br\s*\/?>/i', "\n", $desc);
            $desc = preg_replace('/<\/p>/i', "\n", $desc);
            $desc = strip_tags($desc);
            $desc = html_entity_decode($desc, ENT_QUOTES, 'UTF-8');
            $desc = preg_replace("/\n{3,}/", "\n\n", trim($desc));
            file_put_contents($goodsDir . DIRECTORY_SEPARATOR . '描述.txt', $desc);

            // 价格：按目标国家汇率转换
            $price = (float) $goods->price;
            $convertedPrice = round($price * $rate, $decimals);
            if ($decimals === 0) {
                file_put_contents($goodsDir . DIRECTORY_SEPARATOR . '价格.txt', (string)(int)$convertedPrice);
            } else {
                file_put_contents($goodsDir . DIRECTORY_SEPARATOR . '价格.txt', number_format($convertedPrice, $decimals, '.', ''));
            }

            // 链接：H5 端商品详情页
            $productUrl = rtrim($siteUrl, '/') . '/#/pages/goods/detail?id=' . $goodsId;
            file_put_contents($goodsDir . DIRECTORY_SEPARATOR . '链接.txt', $productUrl);

            // 收集汇总数据
            $summaryRows[] = [$goodsId, $title, $productUrl];

            $images = $goods->images;
            if (is_string($images)) {
                $images = json_decode($images, true) ?: [];
            }

            $imageUrls = [];
            foreach ($images as $img) {
                $imageUrls[] = UrlHelper::getFullUrl($img);
            }
            file_put_contents($goodsDir . DIRECTORY_SEPARATOR . '图片.txt', implode("\n", $imageUrls));

            // 下载前5张图片文件
            foreach (array_slice($imageUrls, 0, 5) as $imgIndex => $imageUrl) {
                $num = $imgIndex + 1;
                try {
                    $ctx = stream_context_create([
                        'http' => ['timeout' => 5],
                        'ssl' => ['verify_peer' => false, 'verify_peer_name' => false],
                    ]);
                    $imageContent = @file_get_contents($imageUrl, false, $ctx);
                    if ($imageContent === false) continue;

                    $ext = 'jpg';
                    $pathInfo = pathinfo(parse_url($imageUrl, PHP_URL_PATH) ?: '');
                    if (isset($pathInfo['extension'])) {
                        $rawExt = strtolower($pathInfo['extension']);
                        if (in_array($rawExt, ['jpg', 'jpeg', 'png', 'gif', 'webp'])) {
                            $ext = $rawExt === 'jpeg' ? 'jpg' : $rawExt;
                        }
                    }

                    file_put_contents($goodsDir . DIRECTORY_SEPARATOR . $num . '.' . $ext, $imageContent);
                } catch (\Throwable $e) {
                    continue;
                }
            }
        }

        // 生成汇总表格 CSV
        $csvPath = $tempDir . DIRECTORY_SEPARATOR . '商品汇总.csv';
        $fp = fopen($csvPath, 'w');
        // 写入 BOM 头（让 Excel 正确识别 UTF-8）
        fwrite($fp, "\xEF\xBB\xBF");
        fputcsv($fp, ['商品ID', '商品名称', '商品链接']);
        foreach ($summaryRows as $row) {
            fputcsv($fp, $row);
        }
        fclose($fp);

        // 创建 ZIP
        $zipPath = $exportDir . DIRECTORY_SEPARATOR . $taskId . '.zip';
        $zip = new \ZipArchive();
        if ($zip->open($zipPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) !== true) {
            file_put_contents($progressFile, json_encode([
                'status' => 'failed',
                'total' => $total,
                'current' => $total,
                'message' => '创建 ZIP 文件失败',
                'file' => '',
            ]));
            return;
        }

        $this->addDirToZip($zip, $tempDir, '');
        $zip->close();
        $this->removeDir($tempDir);

        file_put_contents($progressFile, json_encode([
            'status' => 'completed',
            'total' => $total,
            'current' => $total,
            'message' => '导出完成',
            'file' => $zipPath,
        ]));
    }

    protected function addDirToZip(\ZipArchive $zip, string $dir, string $prefix): void
    {
        foreach (scandir($dir) as $file) {
            if ($file === '.' || $file === '..') continue;
            $filePath = $dir . DIRECTORY_SEPARATOR . $file;
            $zipPath = $prefix ? $prefix . '/' . $file : $file;
            if (is_dir($filePath)) {
                $zip->addEmptyDir($zipPath);
                $this->addDirToZip($zip, $filePath, $zipPath);
            } else {
                $zip->addFile($filePath, $zipPath);
            }
        }
    }

    protected function removeDir(string $dir): void
    {
        if (!is_dir($dir)) return;
        foreach (scandir($dir) as $file) {
            if ($file === '.' || $file === '..') continue;
            $filePath = $dir . DIRECTORY_SEPARATOR . $file;
            is_dir($filePath) ? $this->removeDir($filePath) : unlink($filePath);
        }
        rmdir($dir);
    }

    /**
     * Find PHP 8 binary path
     */
    protected function findPhp8Binary(): string
    {
        $phpStudyBase = 'D:\\phpstudy_pro\\Extensions\\php';
        if (is_dir($phpStudyBase)) {
            $dirs = glob($phpStudyBase . '\\php8.*', GLOB_ONLYDIR);
            if ($dirs) {
                sort($dirs);
                $phpExe = end($dirs) . '\\php.exe';
                if (file_exists($phpExe)) {
                    return $phpExe;
                }
            }
        }
        return PHP_BINARY ?: '/www/server/php/80/bin/php';
    }
}
