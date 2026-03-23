<?php
declare(strict_types=1);

namespace app\admin\controller;

use app\common\model\Language as LanguageModel;
use app\common\model\UiTranslation;
use app\common\service\TranslateService;
use app\common\service\LanguageBatchTranslateService;
use think\Response;
use think\facade\Cache;

/**
 * 语言管理控制器
 */
class Language extends Base
{
    /**
     * 语言列表
     */
    public function index(): Response
    {
        [$page, $pageSize] = $this->getPageParams();

        // 使用原生 Db 查询避免模型缓存问题
        $query = \think\facade\Db::table('languages')->order('sort', 'asc');

        // 搜索条件
        $keyword = input('keyword', '');
        if ($keyword) {
            $query->whereRaw("code LIKE ? OR name LIKE ? OR native_name LIKE ?", [
                "%{$keyword}%", "%{$keyword}%", "%{$keyword}%"
            ]);
        }

        $total = $query->count();
        $list = $query->page($page, $pageSize)->select();

        $data = [];
        foreach ($list as $row) {
            $data[] = [
                'id' => $row['id'],
                'code' => $row['code'],
                'name' => $row['name'],
                'native_name' => $row['native_name'],
                'flag' => $row['flag'],
                'sort' => $row['sort'],
                'is_default' => (bool)$row['is_default'],
                'is_active' => (bool)$row['is_active'],
                'created_at' => $row['created_at'],
                'updated_at' => $row['updated_at'],
            ];
        }

        return $this->paginate($data, $total, $page, $pageSize);
    }

    /**
     * 获取语言详情
     */
    public function read($id): Response
    {
        // 使用原生 Db 查询避免模型缓存问题
        $row = \think\facade\Db::table('languages')->where('id', $id)->find();
        if (!$row) {
            return $this->error('Language not found');
        }

        return $this->success([
            'id' => $row['id'],
            'code' => $row['code'],
            'name' => $row['name'],
            'native_name' => $row['native_name'],
            'flag' => $row['flag'],
            'sort' => $row['sort'],
            'is_default' => (bool)$row['is_default'],
            'is_active' => (bool)$row['is_active'],
            'created_at' => $row['created_at'],
            'updated_at' => $row['updated_at'],
        ]);
    }

    /**
     * 新增语言
     */
    public function create(): Response
    {
        $data = input();

        // 验证必填字段
        if (empty($data['code']) || empty($data['name'])) {
            return $this->error('Language code and name are required');
        }

        // 验证语言代码格式（小写字母和连字符）
        if (!preg_match('/^[a-z]{2}-[a-z]{2}$/', $data['code'])) {
            return $this->error('Invalid language code format. Use format like: en-us, zh-tw');
        }

        // 检查语言代码是否已存在
        if (LanguageModel::codeExists($data['code'])) {
            return $this->error('Language code already exists');
        }

        // 获取最大排序值
        $maxSort = LanguageModel::max('sort') ?: 0;

        $language = new LanguageModel();
        $language->code = strtolower($data['code']);
        $language->name = $data['name'];
        $language->native_name = $data['native_name'] ?? $data['name'];
        $language->flag = $data['flag'] ?? '';
        $language->sort = $data['sort'] ?? ($maxSort + 1);
        $language->is_default = 0; // 新建语言不能直接设为默认
        $language->is_active = $data['is_active'] ?? 1;
        $language->save();

        // 清除语言列表缓存
        Cache::delete('supported_languages');

        return $this->success($language->toApiArray(), 'Language created');
    }

    /**
     * 更新语言
     */
    public function update($id): Response
    {
        $id = (int)$id;
        $language = LanguageModel::find($id);
        if (!$language) {
            return $this->error('Language not found');
        }

        // 获取请求参数 - 直接从 JSON body 读取
        $rawInput = file_get_contents('php://input');
        $data = json_decode($rawInput, true) ?: [];

        // 合并 URL 参数（如果有）
        $data = array_merge(input(), $data);

        // 验证语言代码格式
        if (!empty($data['code'])) {
            if (!preg_match('/^[a-z]{2}-[a-z]{2}$/', $data['code'])) {
                return $this->error('Invalid language code format. Use format like: en-us, zh-tw');
            }
            // 检查语言代码是否已存在（排除自身）
            if (LanguageModel::codeExists($data['code'], $id)) {
                return $this->error('Language code already exists');
            }
            $language->code = strtolower($data['code']);
        }

        if (isset($data['name'])) {
            $language->name = $data['name'];
        }
        if (isset($data['native_name'])) {
            $language->native_name = $data['native_name'];
        }
        if (isset($data['flag'])) {
            $language->flag = $data['flag'];
        }
        if (isset($data['sort'])) {
            $language->sort = (int)$data['sort'];
        }
        if (isset($data['is_active'])) {
            // 默认语言不能禁用
            if ($language->is_default && !$data['is_active']) {
                return $this->error('Cannot disable default language');
            }
            $language->is_active = $data['is_active'] ? 1 : 0;
        }

        // 强制保存（即使 ThinkPHP 认为数据没有变化）
        $language->force()->save();

        // 清除语言列表缓存
        Cache::delete('supported_languages');

        // 重新从数据库获取最新数据（使用原生查询避免缓存）
        $freshData = \think\facade\Db::table('languages')->where('id', $id)->find();

        return $this->success([
            'id' => $freshData['id'],
            'code' => $freshData['code'],
            'name' => $freshData['name'],
            'native_name' => $freshData['native_name'],
            'flag' => $freshData['flag'],
            'sort' => $freshData['sort'],
            'is_default' => (bool)$freshData['is_default'],
            'is_active' => (bool)$freshData['is_active'],
            'created_at' => $freshData['created_at'],
            'updated_at' => $freshData['updated_at'],
        ], 'Language updated');
    }

    /**
     * 删除语言
     */
    public function delete($id): Response
    {
        $language = LanguageModel::find($id);
        if (!$language) {
            return $this->error('Language not found');
        }

        // 默认语言不能删除
        if ($language->is_default) {
            return $this->error('Cannot delete default language');
        }

        // TODO: 检查是否有翻译数据使用此语言

        $language->delete();

        // 清除语言列表缓存
        Cache::delete('supported_languages');

        return $this->success(null, 'Language deleted');
    }

    /**
     * 切换启用状态
     */
    public function updateStatus($id): Response
    {
        $language = LanguageModel::find($id);
        if (!$language) {
            return $this->error('Language not found');
        }

        // 默认语言不能禁用
        if ($language->is_default) {
            return $this->error('Cannot disable default language');
        }

        $language->is_active = $language->is_active ? 0 : 1;
        $language->save();

        // 清除语言列表缓存
        Cache::delete('supported_languages');

        return $this->success([
            'id' => $language->id,
            'is_active' => (bool)$language->is_active
        ], 'Status updated');
    }

    /**
     * 设置为默认语言
     */
    public function setDefault($id): Response
    {
        $language = LanguageModel::find($id);
        if (!$language) {
            return $this->error('Language not found');
        }

        if ($language->is_default) {
            return $this->error('Already default language');
        }

        $language->setAsDefault();

        return $this->success([
            'id' => $language->id,
            'is_default' => true
        ], 'Default language updated');
    }

    /**
     * 更新排序
     */
    public function updateSort(): Response
    {
        $ids = input('ids', []);
        if (empty($ids) || !is_array($ids)) {
            return $this->error('Invalid ids');
        }

        foreach ($ids as $sort => $id) {
            LanguageModel::where('id', $id)->update(['sort' => $sort + 1]);
        }

        return $this->success(null, 'Sort updated');
    }

    /**
     * 获取所有启用的语言（用于下拉选择）
     */
    public function options(): Response
    {
        $list = LanguageModel::getActiveOptions();
        return $this->success($list);
    }

    /**
     * 触发翻译任务
     * 分批执行翻译，前端轮询进度
     * 支持选择翻译类型：all（全部）, ui（仅UI）, content（仅内容）, goods（仅商品相关）
     */
    public function translate($id): Response
    {
        // 增加执行时间限制
        set_time_limit(0);
        ini_set('max_execution_time', '0');
        ignore_user_abort(true);

        $language = LanguageModel::find($id);
        if (!$language) {
            return $this->error('Language not found');
        }

        $translateService = new TranslateService();
        if (!$translateService->isConfigured()) {
            return $this->error('Translation API not configured. Please configure translate_api_provider and translate_api_key in system settings.');
        }

        $sourceLocale = input('source_locale', 'en-us');
        $translateType = input('type', 'ui'); // 默认只翻译 UI（速度快）
        $force = input('force', false); // 是否强制重新翻译

        // 默认每批处理的记录数
        $batchSize = 30;
        // 含长文本（description/content）字段的表使用更小的批次，避免翻译 API 超时
        $smallBatchTables = [
            'goods_translations', 'help_article_translations', 'page_translations',
            'email_template_translations', 'notification_template_translations',
        ];

        // 批次翻译逻辑
        try {
            $batchService = new LanguageBatchTranslateService();
            $progressKey = 'translate_progress:' . $language->code;

            // 检查是否是继续翻译
            $progress = Cache::get($progressKey);

            // 确定内容翻译的 scope
            $contentScope = null;
            $singleTable = null;
            if ($translateType === 'goods') {
                $contentScope = 'goods';
            } elseif ($translateType === 'single') {
                $singleTable = input('table', '');
                if (!$singleTable) {
                    return $this->error('Please specify a table name');
                }
                // 验证表名是否在配置列表中
                $allTables = $batchService->getTranslationTables();
                if (!isset($allTables[$singleTable])) {
                    return $this->error('Invalid table name: ' . $singleTable);
                }
            }
            $needsUi = in_array($translateType, ['ui', 'all']);
            $needsContent = in_array($translateType, ['content', 'goods', 'single', 'all']);

            // 初始化或重新开始
            if (!$progress || input('restart', false) || ($progress['status'] ?? '') === 'completed') {
                $progress = [
                    'status' => 'running',
                    'type' => $translateType,
                    'force' => $force,
                    'source_locale' => $sourceLocale,
                    'current_step' => 'ui',
                    'content_scope' => $contentScope,
                    'single_table' => $singleTable,
                    // UI 翻译进度
                    'ui_done' => !$needsUi,
                    'ui_offset' => 0,
                    'ui_initial_total' => 0,
                    'ui_results' => [
                        'total' => 0,
                        'translated' => 0,
                        'skipped' => 0,
                        'failed' => 0,
                    ],
                    // 内容翻译进度
                    'content_done' => !$needsContent,
                    'current_table_index' => 0,
                    'current_table_offset' => 0,
                    'content_tables_done' => 0,
                    'content_tables_total' => $singleTable ? 1 : count($batchService->getTranslationTables($contentScope)),
                    'content_results' => [
                        'locale' => $language->code,
                        'source_locale' => $sourceLocale,
                        'tables' => [],
                        'total_records' => 0,
                        'translated_records' => 0,
                        'failed_records' => 0,
                    ],
                    'started_at' => date('Y-m-d H:i:s'),
                ];

                // 强制重新翻译时删除已有数据
                if ($force) {
                    if ($needsUi) {
                        UiTranslation::deleteByLocale($language->code);
                    }
                    if ($needsContent) {
                        if ($singleTable) {
                            // 单表模式：只删除指定表的翻译
                            $batchService->deleteTranslationsForTable($singleTable, $language->code);
                        } else {
                            $batchService->deleteTranslationsForLanguage($language->code, $contentScope);
                        }
                    }
                }

                Cache::set($progressKey, $progress, 7200); // 2小时过期
            }

            // 步骤1：翻译 UI（分批处理）
            if (!$progress['ui_done']) {
                $uiBatchResult = UiTranslation::generateTranslationsForLocale(
                    $language->code,
                    $progress['source_locale'],
                    $batchSize,
                    $progress['ui_offset']
                );

                // 累加结果
                // 首次请求时记录初始总数，后续请求中 total 会减少（因为已翻译的被排除了）
                if ($progress['ui_initial_total'] === 0 && $uiBatchResult['total'] > 0) {
                    $progress['ui_initial_total'] = $uiBatchResult['total'];
                }
                $progress['ui_results']['total'] = $progress['ui_initial_total'] ?: $uiBatchResult['total'];
                $progress['ui_results']['translated'] += $uiBatchResult['translated'];
                $progress['ui_results']['skipped'] += $uiBatchResult['skipped'] ?? 0;
                $progress['ui_results']['failed'] += $uiBatchResult['failed'];

                // 已完成的总数
                $uiDoneCount = $progress['ui_results']['translated'] + ($progress['ui_results']['skipped'] ?? 0) + $progress['ui_results']['failed'];
                $uiTotalCount = $progress['ui_results']['total'];

                if ($uiBatchResult['has_more']) {
                    // 还有更多 UI 翻译需要处理
                    $progress['ui_offset'] = $uiBatchResult['next_offset'];
                    Cache::set($progressKey, $progress, 7200);

                    return $this->success([
                        'status' => 'running',
                        'has_more' => true,
                        'current_step' => 'ui',
                        'current_table' => 'ui_translations',
                        'tables_done' => 0,
                        'tables_total' => $progress['content_tables_total'] + 1,
                        'ui_progress' => [
                            'done' => $uiDoneCount,
                            'total' => $uiTotalCount,
                        ],
                        'record_progress' => [
                            'table' => 'ui_translations',
                            'table_translated' => $uiDoneCount,
                            'table_total' => $uiTotalCount,
                            'batch_translated' => $uiBatchResult['translated'],
                            'batch_failed' => $uiBatchResult['failed'],
                            'overall_translated' => $uiDoneCount,
                            'overall_failed' => $progress['ui_results']['failed'],
                        ],
                    ]);
                }

                // UI 翻译完成
                $progress['ui_done'] = true;
                $progress['current_step'] = 'content';
                Cache::delete('ui_translations:' . $language->code);
                Cache::set($progressKey, $progress, 7200);

                // 如果只翻译 UI，直接完成
                if ($translateType === 'ui') {
                    $progress['status'] = 'completed';
                    $progress['finished_at'] = date('Y-m-d H:i:s');
                    Cache::set($progressKey, $progress, 7200);

                    return $this->success([
                        'status' => 'completed',
                        'has_more' => false,
                        'ui_translations' => $progress['ui_results'],
                    ], 'Translation completed');
                }

                // 返回让前端继续请求内容翻译
                return $this->success([
                    'status' => 'running',
                    'has_more' => true,
                    'current_step' => 'content',
                    'ui_translations' => $progress['ui_results'],
                    'tables_done' => 1,
                    'tables_total' => $progress['content_tables_total'] + 1,
                ]);
            }

            // 步骤2：翻译内容表（分批处理）
            if (!$progress['content_done']) {
                // 单表翻译模式
                if (!empty($progress['single_table'])) {
                    $allTables = $batchService->getTranslationTables();
                    $singleTableName = $progress['single_table'];
                    $tables = [$singleTableName => $allTables[$singleTableName]];
                } else {
                    $tables = $batchService->getTranslationTables($progress['content_scope'] ?? null);
                }
                $tableNames = array_keys($tables);
                $tableIndex = $progress['current_table_index'];
                $offset = $progress['current_table_offset'];

                // 检查是否所有表都已处理完成
                if ($tableIndex >= count($tableNames)) {
                    $progress['status'] = 'completed';
                    $progress['content_done'] = true;
                    $progress['finished_at'] = date('Y-m-d H:i:s');
                    Cache::set($progressKey, $progress, 7200);

                    return $this->success([
                        'status' => 'completed',
                        'has_more' => false,
                        'ui_translations' => $progress['ui_results'],
                        'content_results' => $progress['content_results'],
                    ], 'Translation completed');
                }

                // 翻译当前表的一批记录
                $tableName = $tableNames[$tableIndex];
                $tableConfig = $tables[$tableName];

                // 含长文本字段的表使用更小的批次（10条），避免单次请求耗时过长
                $currentBatchSize = in_array($tableName, $smallBatchTables) ? 10 : $batchSize;

                $batchResult = $batchService->translateSingleTable(
                    $tableName,
                    $tableConfig,
                    $language->code,
                    $progress['source_locale'],
                    $currentBatchSize,
                    $offset
                );

                // 更新统计
                if (!isset($progress['content_results']['tables'][$tableName])) {
                    $progress['content_results']['tables'][$tableName] = [
                        'total' => $batchResult['total'],
                        'translated' => 0,
                        'failed' => 0,
                        'skipped' => 0,
                    ];
                }
                $progress['content_results']['tables'][$tableName]['translated'] += $batchResult['translated'];
                $progress['content_results']['tables'][$tableName]['failed'] += $batchResult['failed'];
                $progress['content_results']['translated_records'] += $batchResult['translated'];
                $progress['content_results']['failed_records'] += $batchResult['failed'];

                // 判断是否还有更多记录
                if ($batchResult['has_more']) {
                    // 同一个表还有更多记录
                    $progress['current_table_offset'] = $batchResult['next_offset'];
                } else {
                    // 当前表完成，移到下一个表
                    $progress['content_results']['total_records'] += $batchResult['total'];
                    $progress['current_table_index']++;
                    $progress['current_table_offset'] = 0;
                    $progress['content_tables_done']++;
                }

                Cache::set($progressKey, $progress, 7200);

                $hasMore = $progress['current_table_index'] < count($tableNames);

                // 计算总进度（UI 算 1 个表）
                $uiTableCount = ($translateType === 'all' || $translateType === 'ui') ? 1 : 0;
                $tablesDone = $uiTableCount + $progress['content_tables_done'];
                $tablesTotal = $uiTableCount + count($tableNames);

                // 计算当前表的翻译进度
                $currentTableTranslated = $progress['content_results']['tables'][$tableName]['translated'] ?? 0;
                $currentTableTotal = $batchResult['total'] + $currentTableTranslated;

                return $this->success([
                    'status' => $hasMore ? 'running' : 'completed',
                    'has_more' => $hasMore,
                    'current_step' => 'content',
                    'current_table' => $tableName,
                    'tables_done' => $tablesDone,
                    'tables_total' => $tablesTotal,
                    'content_results' => $hasMore ? null : $progress['content_results'],
                    // 记录级别的进度信息
                    'record_progress' => [
                        'table' => $tableName,
                        'table_translated' => $currentTableTranslated,
                        'table_total' => $currentTableTotal,
                        'batch_translated' => $batchResult['translated'],
                        'batch_failed' => $batchResult['failed'],
                        'overall_translated' => $progress['content_results']['translated_records'],
                        'overall_failed' => $progress['content_results']['failed_records'],
                    ],
                ], $hasMore ? 'Batch completed' : 'Translation completed');
            }

            // 所有翻译完成
            $progress['status'] = 'completed';
            $progress['finished_at'] = date('Y-m-d H:i:s');
            Cache::set($progressKey, $progress, 7200);

            return $this->success([
                'status' => 'completed',
                'has_more' => false,
                'ui_translations' => $progress['ui_results'],
                'content_results' => $progress['content_results'],
            ], 'Translation completed');

        } catch (\Exception $e) {
            \think\facade\Log::error('Translation error: ' . $e->getMessage());
            // 更新进度为错误状态
            $progressKey = 'translate_progress:' . $language->code;
            $progress = Cache::get($progressKey);
            if ($progress) {
                $progress['status'] = 'error';
                $progress['error'] = $e->getMessage();
                Cache::set($progressKey, $progress, 7200);
            }
            return $this->error('Translation failed: ' . $e->getMessage());
        }
    }

    /**
     * 获取翻译进度
     */
    public function translateProgress($id): Response
    {
        $language = LanguageModel::find($id);
        if (!$language) {
            return $this->error('Language not found');
        }

        $batchService = new LanguageBatchTranslateService();
        $progress = $batchService->getProgress($language->code);

        return $this->success($progress);
    }

    /**
     * 获取翻译统计
     */
    public function translateStats($id): Response
    {
        $language = LanguageModel::find($id);
        if (!$language) {
            return $this->error('Language not found');
        }

        $batchService = new LanguageBatchTranslateService();
        $contentStats = $batchService->getTranslationStats($language->code);
        $uiStats = UiTranslation::getStats($language->code);

        return $this->success([
            'locale' => $language->code,
            'content_tables' => $contentStats,
            'ui_translations' => $uiStats,
        ]);
    }

    /**
     * 获取翻译 API 配置状态
     */
    /**
     * 获取可翻译的内容表列表
     */
    public function translationTables(): Response
    {
        $batchService = new LanguageBatchTranslateService();
        $tables = $batchService->getTranslationTables();

        $result = [];
        foreach ($tables as $tableName => $config) {
            $result[] = [
                'table' => $tableName,
                'fields' => $config['fields'],
                'label' => $this->getTableLabel($tableName),
            ];
        }

        return $this->success($result);
    }

    /**
     * 获取翻译表的中文标签
     */
    private function getTableLabel(string $tableName): string
    {
        $labels = [
            'goods_translations' => '商品',
            'category_translations' => '分类',
            'brand_translations' => '品牌',
            'banner_translations' => '横幅广告',
            'coupon_translations' => '优惠券',
            'category_attribute_translations' => '分类属性',
            'attribute_option_translations' => '属性选项',
            'system_config_translations' => '系统配置',
            'payment_method_translations' => '支付方式',
            'customer_service_translations' => '客服',
            'help_category_translations' => '帮助分类',
            'help_article_translations' => '帮助文章',
            'category_condition_group_translations' => '成色分组',
            'category_condition_option_translations' => '成色选项',
            'notification_template_translations' => '通知模板',
            'page_translations' => '页面',
            'shipping_carrier_translations' => '物流商',
            'promotion_translations' => '促销活动',
            'country_translations' => '国家/地区',
            'email_template_translations' => '邮件模板',
            'installment_plan_translations' => '分期方案',
            'quick_reply_translations' => '快捷回复',
            'sell_faq_translations' => '卖家FAQ',
            'shop_translations' => '店铺',
            'game_translations' => '游戏',
            'game_prize_translations' => '游戏奖品',
            'treasure_box_translations' => '宝箱',
            'treasure_box_prize_translations' => '宝箱奖品',
            'egg_tier_translations' => '蛋分级',
            'egg_tier_prize_translations' => '蛋奖品',
            'withdrawal_method_translations' => '提现方式',
        ];

        return $labels[$tableName] ?? $tableName;
    }

    public function translateConfig(): Response
    {
        $translateService = new TranslateService();

        return $this->success([
            'configured' => $translateService->isConfigured(),
            'provider' => $translateService->getProvider(),
        ]);
    }

    /**
     * 检查翻译状态
     * 对比默认语言（en-us）与目标语言的翻译覆盖情况
     */
    public function checkTranslationStatus($id): Response
    {
        $language = LanguageModel::find($id);
        if (!$language) {
            return $this->error('Language not found');
        }

        $targetLocale = $language->code;

        // 获取默认语言代码
        $defaultLanguage = LanguageModel::where('is_default', 1)->find();
        $sourceLocale = $defaultLanguage ? $defaultLanguage->code : 'en-us';

        // 如果检查的就是默认语言，跳过对比
        if ($targetLocale === $sourceLocale) {
            return $this->error('Cannot check translation status for the default language');
        }

        $result = [
            'locale' => $targetLocale,
            'source_locale' => $sourceLocale,
            'ui' => $this->checkUiTranslationStatus($sourceLocale, $targetLocale),
            'content' => $this->checkContentTranslationStatus($sourceLocale, $targetLocale),
        ];

        // 计算总体统计
        $totalSource = $result['ui']['source_total'];
        $totalTarget = $result['ui']['target_total'];
        $totalMissing = $result['ui']['missing_count'];

        foreach ($result['content'] as $table) {
            $totalSource += $table['source_total'];
            $totalTarget += $table['target_total'];
            $totalMissing += $table['missing_count'];
        }

        $result['summary'] = [
            'total_source' => $totalSource,
            'total_target' => $totalTarget,
            'total_missing' => $totalMissing,
            'completion_rate' => $totalSource > 0 ? round(($totalTarget / $totalSource) * 100, 1) : 100,
        ];

        return $this->success($result);
    }

    /**
     * 检查 UI 翻译状态
     */
    private function checkUiTranslationStatus(string $sourceLocale, string $targetLocale): array
    {
        $db = \think\facade\Db::class;

        // 源语言按命名空间统计
        $sourceStats = \think\facade\Db::table('ui_translations')
            ->where('locale', $sourceLocale)
            ->group('namespace')
            ->column('count(*) as count', 'namespace');

        // 目标语言按命名空间统计
        $targetStats = \think\facade\Db::table('ui_translations')
            ->where('locale', $targetLocale)
            ->group('namespace')
            ->column('count(*) as count', 'namespace');

        $namespaces = [];
        $totalSource = 0;
        $totalTarget = 0;
        $totalMissing = 0;

        foreach ($sourceStats as $ns => $sourceCount) {
            $targetCount = $targetStats[$ns] ?? 0;
            $missing = $sourceCount - $targetCount;
            if ($missing < 0) $missing = 0;

            $namespaces[] = [
                'namespace' => $ns,
                'source_count' => $sourceCount,
                'target_count' => $targetCount,
                'missing_count' => $missing,
                'completion_rate' => $sourceCount > 0 ? round(($targetCount / $sourceCount) * 100, 1) : 100,
            ];

            $totalSource += $sourceCount;
            $totalTarget += $targetCount;
            $totalMissing += $missing;
        }

        // 按缺失数量降序排列
        usort($namespaces, function ($a, $b) {
            return $b['missing_count'] - $a['missing_count'];
        });

        return [
            'source_total' => $totalSource,
            'target_total' => $totalTarget,
            'missing_count' => $totalMissing,
            'completion_rate' => $totalSource > 0 ? round(($totalTarget / $totalSource) * 100, 1) : 100,
            'namespaces' => $namespaces,
        ];
    }

    /**
     * 检查内容翻译状态
     */
    private function checkContentTranslationStatus(string $sourceLocale, string $targetLocale): array
    {
        $batchService = new LanguageBatchTranslateService();
        $tables = $batchService->getTranslationTables();

        $result = [];

        foreach ($tables as $tableName => $config) {
            // 检查表是否存在
            try {
                // 获取外键字段名（用于计算基准记录数）
                $foreignKey = $config['foreign_key'] ?? null;

                // 基准数：源语言的记录数
                $sourceCount = \think\facade\Db::table($tableName)
                    ->where('locale', $sourceLocale)
                    ->count();

                // 如果源语言没有数据，尝试用唯一外键数作为基准（可能是其他语言先创建的数据）
                if ($sourceCount === 0 && $foreignKey) {
                    $sourceCount = \think\facade\Db::table($tableName)
                        ->field("COUNT(DISTINCT {$foreignKey}) as cnt")
                        ->findOrEmpty();
                    $sourceCount = (int)($sourceCount['cnt'] ?? 0);
                }

                $targetCount = \think\facade\Db::table($tableName)
                    ->where('locale', $targetLocale)
                    ->count();
            } catch (\Exception $e) {
                // 表不存在，跳过
                continue;
            }

            // 两者都为 0，跳过不显示
            if ($sourceCount === 0 && $targetCount === 0) {
                continue;
            }

            $missing = $sourceCount - $targetCount;
            if ($missing < 0) $missing = 0;

            $result[] = [
                'table' => $tableName,
                'label' => $this->getTableLabel($tableName),
                'source_total' => $sourceCount,
                'target_total' => $targetCount,
                'missing_count' => $missing,
                'completion_rate' => $sourceCount > 0 ? round(($targetCount / $sourceCount) * 100, 1) : 100,
            ];
        }

        // 按缺失数量降序排列
        usort($result, function ($a, $b) {
            return $b['missing_count'] - $a['missing_count'];
        });

        return $result;
    }
}
