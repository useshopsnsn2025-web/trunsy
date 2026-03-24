<?php
/**
 * 采集导入后台工作进程
 * 用法: php crawl_worker.php <task_id>
 * 逐个处理商品：深度采集 → AI描述 → 入库，每个商品独立完成后更新进度
 */

use think\facade\Db;
use app\common\service\EbayCrawlerService;
use app\common\service\GeminiService;
use app\common\service\DeepSeekService;
use app\common\service\TranslateService;
use app\common\model\Goods as GoodsModel;
use app\common\model\GoodsTranslation;
use app\common\model\GoodsConditionValue;

// 初始化 ThinkPHP 应用
require __DIR__ . '/vendor/autoload.php';
$app = new \think\App(__DIR__);
$app->initialize();

set_time_limit(0);

// 获取任务ID
$taskId = $argv[1] ?? '';
if (empty($taskId)) {
    exit('Missing task_id');
}

$taskDir = __DIR__ . DIRECTORY_SEPARATOR . 'runtime' . DIRECTORY_SEPARATOR . 'crawl_tasks' . DIRECTORY_SEPARATOR;
$taskFile = $taskDir . $taskId . '.json';
$progressFile = $taskDir . $taskId . '_progress.json';

if (!file_exists($taskFile)) {
    exit('Task file not found');
}

$taskData = json_decode(file_get_contents($taskFile), true);
if (!$taskData) {
    exit('Invalid task data');
}

$items = $taskData['items'];
$config = $taskData['config'];
$userIds = $taskData['user_ids'];

$categoryId = (int) ($config['category_id'] ?? 0);
$type = (int) ($config['type'] ?? 2);
$status = (int) ($config['status'] ?? 0);
$autoTranslate = (bool) ($config['auto_translate'] ?? true);
$priceAdjust = (float) ($config['price_adjust'] ?? 0);
$freeShipping = (int) ($config['free_shipping'] ?? 0);
$specs = $config['specs'] ?? [];
$conditionValues = $config['condition_values'] ?? [];

$crawler = new EbayCrawlerService();
$deepseek = new DeepSeekService();
$gemini = new GeminiService();
$translateService = $autoTranslate ? new TranslateService() : null;

$total = count($items);
$successCount = 0;
$failCount = 0;
$errors = [];
$goodsIds = [];

/**
 * 更新进度文件
 */
function updateProgress($file, $data) {
    file_put_contents($file, json_encode($data, JSON_UNESCAPED_UNICODE));
}

// 逐个处理商品
foreach ($items as $index => $item) {
    $title = $item['title'] ?? 'Unknown';

    // 更新进度：正在处理
    updateProgress($progressFile, [
        'status'        => 'running',
        'total'         => $total,
        'processed'     => $index,
        'success_count' => $successCount,
        'fail_count'    => $failCount,
        'current_title' => $title,
        'goods_ids'     => $goodsIds,
        'errors'        => $errors,
        'started_at'    => $taskData['created_at'] ?? '',
    ]);

    try {
        // 1. 深度采集（如果只有1张图）
        if (count($item['image_urls'] ?? []) <= 1 && !empty($item['listing_url'])) {
            try {
                $result = $crawler->crawlDetail([
                    [
                        'ebay_item_id' => $item['ebay_item_id'] ?? '',
                        'listing_url'  => $item['listing_url'],
                    ]
                ]);
                $detailItems = $result['items'] ?? [];
                if (!empty($detailItems[0]['image_urls'])) {
                    $item['image_urls'] = $detailItems[0]['image_urls'];
                    $item['image_url'] = $detailItems[0]['image_urls'][0];
                }
            } catch (\Exception $e) {
                // 深度采集失败不影响导入
            }
        }

        // 2. AI 描述生成
        $descriptions = [];
        if ($deepseek->isConfigured()) {
            try {
                $batch = $deepseek->batchGenerateDescriptions([$item]);
                $descriptions = $batch[0] ?? [];
            } catch (\Exception $e) {
                // ignore
            }
        }
        if (empty($descriptions) && $gemini->isConfigured()) {
            try {
                $batch = $gemini->batchGenerateDescriptions([$item]);
                $descriptions = $batch[0] ?? [];
            } catch (\Exception $e) {
                // ignore
            }
        }

        // 3. 重连数据库（防止超时）
        try {
            @Db::connect()->close();
        } catch (\Throwable $e) {
            // ignore
        }

        // 4. 入库
        $goodsId = createGoods($item, [
            'category_id'      => $categoryId,
            'type'             => $type,
            'status'           => $status,
            'user_id'          => $userIds[array_rand($userIds)],
            'descriptions'     => $descriptions,
            'translateService' => $translateService,
            'autoTranslate'    => $autoTranslate,
            'price_adjust'     => $priceAdjust,
            'free_shipping'    => $freeShipping,
            'specs'            => $specs,
            'condition_values' => $conditionValues,
        ]);

        $goodsIds[] = $goodsId;
        $successCount++;
    } catch (\Exception $e) {
        $failCount++;
        $errors[] = [
            'title'  => $title,
            'reason' => $e->getMessage(),
        ];
    }
}

// 完成
updateProgress($progressFile, [
    'status'        => 'completed',
    'total'         => $total,
    'processed'     => $total,
    'success_count' => $successCount,
    'fail_count'    => $failCount,
    'current_title' => '',
    'goods_ids'     => $goodsIds,
    'errors'        => $errors,
    'started_at'    => $taskData['created_at'] ?? '',
    'completed_at'  => date('Y-m-d H:i:s'),
]);

// 清理任务数据文件（保留进度文件供查询）
@unlink($taskFile);


/**
 * 创建单个商品
 */
function createGoods(array $item, array $config): int
{
    $goodsNo = 'G' . date('YmdHis') . str_pad((string) mt_rand(1, 9999), 4, '0', STR_PAD_LEFT);

    $goods = new GoodsModel();
    $goods->goods_no = $goodsNo;
    $goods->user_id = $config['user_id'];
    $goods->category_id = $config['category_id'];
    $goods->type = $config['type'];
    $goods->condition = $item['condition_mapped'] ?? 1;
    $priceAdjust = (float) ($config['price_adjust'] ?? 0);
    $goods->price = max(0, ($item['price'] ?? 0) + $priceAdjust);
    $goods->original_price = max(0, ($item['original_price'] ?? 0) + $priceAdjust);
    $goods->currency = $item['currency'] ?? 'USD';
    $goods->stock = 1;
    $goods->images = $item['image_urls'] ?? [];
    $goods->location_country = $item['location'] ?? '';
    $goods->shipping_fee = 0;
    $goods->free_shipping = !empty($config['free_shipping']) ? 1 : ((stripos($item['shipping'] ?? '', 'free') !== false) ? 1 : 0);
    $goods->is_negotiable = 0;
    $goods->likes = mt_rand(10, 50);
    $goods->status = $config['status'];
    $goods->save();

    // 构建翻译
    $enTitle = $item['title'] ?? '';
    $descriptions = $config['descriptions'] ?? [];
    $specs = !empty($config['specs']) ? $config['specs'] : null;

    // en-us
    $enDesc = $descriptions['en-us'] ?? '';
    saveTranslation($goods->id, 'en-us', $enTitle, $enDesc, $specs, true);

    // zh-tw
    $zhTitle = $enTitle;
    $zhDesc = $descriptions['zh-tw'] ?? '';
    if ($config['autoTranslate'] && $config['translateService'] && !empty($enTitle)) {
        try {
            $translated = $config['translateService']->batchTranslate([$enTitle], 'en-us', 'zh-tw');
            if (!empty($translated[0])) {
                $zhTitle = $translated[0];
            }
        } catch (\Exception $e) {
            // ignore
        }
    }
    saveTranslation($goods->id, 'zh-tw', $zhTitle, $zhDesc, $specs, false);

    // ja-jp
    $jaTitle = $enTitle;
    $jaDesc = $descriptions['ja-jp'] ?? '';
    if ($config['autoTranslate'] && $config['translateService'] && !empty($enTitle)) {
        try {
            $translated = $config['translateService']->batchTranslate([$enTitle], 'en-us', 'ja-jp');
            if (!empty($translated[0])) {
                $jaTitle = $translated[0];
            }
        } catch (\Exception $e) {
            // ignore
        }
    }
    saveTranslation($goods->id, 'ja-jp', $jaTitle, $jaDesc, $specs, false);

    // 保存条件值
    $rawConditions = $config['condition_values'] ?? [];
    if (!empty($rawConditions)) {
        $conditionMap = [];
        foreach ($rawConditions as $cv) {
            if (!empty($cv['group_id']) && !empty($cv['option_id'])) {
                $conditionMap[(int) $cv['group_id']] = (int) $cv['option_id'];
            }
        }
        if (!empty($conditionMap)) {
            GoodsConditionValue::saveGoodsConditions($goods->id, $conditionMap);
        }
    }

    return (int) $goods->id;
}

/**
 * 保存商品翻译
 */
function saveTranslation(int $goodsId, string $locale, string $title, string $description, $specs, bool $isOriginal): void
{
    $translation = new GoodsTranslation();
    $translation->goods_id = $goodsId;
    $translation->locale = $locale;
    $translation->title = $title;
    $translation->description = $description;
    $translation->specs = $specs;
    $translation->is_original = $isOriginal ? 1 : 0;
    $translation->is_auto_translated = $isOriginal ? 0 : 1;
    $translation->translated_at = date('Y-m-d H:i:s');
    $translation->save();
}
