<?php
declare(strict_types=1);

namespace app\common\service;

use think\facade\Db;
use think\facade\Log;
use think\facade\Cache;

/**
 * 语言批量翻译服务
 * 为新语言生成所有翻译表的翻译内容
 */
class LanguageBatchTranslateService
{
    /** @var TranslateService */
    protected $translator;

    // 翻译表配置：表名 => [外键字段, 可翻译字段列表, 是否支持额外字段]
    /** @var array */
    protected $translationTables = [
        'goods_translations' => [
            'foreign_key' => 'goods_id',
            'fields' => ['title', 'description'],
            'has_extra_fields' => true, // is_original, is_auto_translated, translated_at
        ],
        'category_translations' => [
            'foreign_key' => 'category_id',
            'fields' => ['name', 'description'],
            'has_extra_fields' => false,
        ],
        'brand_translations' => [
            'foreign_key' => 'brand_id',
            'fields' => ['name', 'description'],
            'has_extra_fields' => true,
        ],
        'banner_translations' => [
            'foreign_key' => 'banner_id',
            'fields' => ['title', 'subtitle'],
            'has_extra_fields' => false,
        ],
        'coupon_translations' => [
            'foreign_key' => 'coupon_id',
            'fields' => ['name', 'description'],
            'has_extra_fields' => false,
        ],
        'category_attribute_translations' => [
            'foreign_key' => 'attribute_id',
            'fields' => ['name', 'placeholder'],
            'has_extra_fields' => false,
        ],
        'attribute_option_translations' => [
            'foreign_key' => 'option_id',
            'fields' => ['label'],
            'has_extra_fields' => false,
        ],
        'system_config_translations' => [
            'foreign_key' => 'config_id',
            'fields' => ['name', 'description'],
            'has_extra_fields' => true,
        ],
        'payment_method_translations' => [
            'foreign_key' => 'payment_method_id',
            'fields' => ['name', 'description'],
            'has_extra_fields' => true,
        ],
        'customer_service_translations' => [
            'foreign_key' => 'service_id',
            'fields' => ['name'],
            'has_extra_fields' => false,
        ],
        'help_category_translations' => [
            'foreign_key' => 'category_id',
            'fields' => ['name'],
            'has_extra_fields' => false,
        ],
        'help_article_translations' => [
            'foreign_key' => 'article_id',
            'fields' => ['title', 'content'],
            'has_extra_fields' => false,
        ],
        'category_condition_group_translations' => [
            'foreign_key' => 'group_id',
            'fields' => ['name'],
            'has_extra_fields' => false,
        ],
        'category_condition_option_translations' => [
            'foreign_key' => 'option_id',
            'fields' => ['name'],
            'has_extra_fields' => false,
        ],
        'notification_template_translations' => [
            'foreign_key' => 'template_id',
            'fields' => ['title', 'content'],
            'has_extra_fields' => false,
        ],
        'page_translations' => [
            'foreign_key' => 'page_id',
            'fields' => ['title', 'content'],
            'has_extra_fields' => false,
        ],
        'shipping_carrier_translations' => [
            'foreign_key' => 'carrier_id',
            'fields' => ['name', 'description'],
            'has_extra_fields' => true,
        ],
        'promotion_translations' => [
            'foreign_key' => 'promotion_id',
            'fields' => ['name', 'description'],
            'has_extra_fields' => false,
        ],
        'country_translations' => [
            'foreign_key' => 'country_id',
            'fields' => ['name'],
            'has_extra_fields' => false,
        ],
        'email_template_translations' => [
            'foreign_key' => 'template_id',
            'fields' => ['subject', 'content'],
            'has_extra_fields' => false,
        ],
        'installment_plan_translations' => [
            'foreign_key' => 'plan_id',
            'fields' => ['name', 'description'],
            'has_extra_fields' => false,
        ],
        'quick_reply_translations' => [
            'foreign_key' => 'reply_id',
            'fields' => ['title', 'content'],
            'has_extra_fields' => false,
        ],
        'sell_faq_translations' => [
            'foreign_key' => 'faq_id',
            'fields' => ['question', 'answer'],
            'has_extra_fields' => false,
        ],
        'shop_translations' => [
            'foreign_key' => 'shop_id',
            'fields' => ['name', 'description', 'announcement'],
            'has_extra_fields' => true, // is_original
        ],
        'game_translations' => [
            'foreign_key' => 'game_id',
            'fields' => ['name', 'description', 'rules'],
            'has_extra_fields' => false,
        ],
        'game_prize_translations' => [
            'foreign_key' => 'prize_id',
            'fields' => ['name', 'description'],
            'has_extra_fields' => false,
        ],
        'treasure_box_translations' => [
            'foreign_key' => 'box_id',
            'fields' => ['name', 'description'],
            'has_extra_fields' => false,
        ],
        'treasure_box_prize_translations' => [
            'foreign_key' => 'prize_id',
            'fields' => ['name', 'description'],
            'has_extra_fields' => false,
        ],
        'egg_tier_translations' => [
            'foreign_key' => 'egg_id',
            'fields' => ['name', 'description'],
            'has_extra_fields' => false,
        ],
        'egg_tier_prize_translations' => [
            'foreign_key' => 'prize_id',
            'fields' => ['name', 'description'],
            'has_extra_fields' => false,
        ],
        'withdrawal_method_translations' => [
            'foreign_key' => 'method_id',
            'fields' => ['name'],
            'has_extra_fields' => false,
        ],
    ];

    // 翻译进度缓存键前缀
    const PROGRESS_CACHE_PREFIX = 'lang_translate_progress:';

    public function __construct()
    {
        $this->translator = new TranslateService();
    }

    /**
     * 为新语言生成所有翻译
     */
    public function generateTranslationsForLanguage(
        string $newLocale,
        string $sourceLocale = 'en-us',
        ?callable $progressCallback = null
    ): array {
        $results = [
            'locale' => $newLocale,
            'source_locale' => $sourceLocale,
            'tables' => [],
            'total_records' => 0,
            'translated_records' => 0,
            'failed_records' => 0,
            'started_at' => date('Y-m-d H:i:s'),
            'finished_at' => null,
        ];

        if (!$this->translator->isConfigured()) {
            $results['error'] = 'Translation API not configured';
            return $results;
        }

        // 初始化进度
        $this->updateProgress($newLocale, [
            'status' => 'running',
            'current_table' => '',
            'tables_done' => 0,
            'tables_total' => count($this->translationTables),
            'records_done' => 0,
            'records_total' => 0,
        ]);

        $tableIndex = 0;
        foreach ($this->translationTables as $table => $config) {
            $tableIndex++;

            // 检查表是否存在
            if (!$this->tableExists($table)) {
                Log::info("Table {$table} does not exist, skipping");
                continue;
            }

            $this->updateProgress($newLocale, [
                'current_table' => $table,
                'tables_done' => $tableIndex - 1,
            ]);

            try {
                $tableResult = $this->translateTable($table, $config, $newLocale, $sourceLocale);
                $results['tables'][$table] = $tableResult;
                $results['total_records'] += $tableResult['total'];
                $results['translated_records'] += $tableResult['translated'];
                $results['failed_records'] += $tableResult['failed'];

                $this->updateProgress($newLocale, [
                    'records_done' => $results['translated_records'],
                    'tables_done' => $tableIndex,
                ]);

                if ($progressCallback) {
                    $progressCallback($table, $tableResult);
                }
            } catch (\Exception $e) {
                Log::error("Error translating table {$table}: " . $e->getMessage());
                $results['tables'][$table] = [
                    'error' => $e->getMessage(),
                    'total' => 0,
                    'translated' => 0,
                    'failed' => 0,
                ];
            }
        }

        $results['finished_at'] = date('Y-m-d H:i:s');

        // 更新进度为完成
        $this->updateProgress($newLocale, [
            'status' => 'completed',
            'current_table' => '',
            'tables_done' => count($this->translationTables),
            'records_done' => $results['translated_records'],
            'records_total' => $results['total_records'],
            'finished_at' => $results['finished_at'],
        ]);

        return $results;
    }

    /**
     * 商品相关的翻译表
     */
    protected const GOODS_TABLES = [
        'goods_translations',
        'category_translations',
        'brand_translations',
        'category_attribute_translations',
        'attribute_option_translations',
        'category_condition_group_translations',
        'category_condition_option_translations',
    ];

    /**
     * 获取翻译表配置列表
     * @param string|null $scope null=全部, 'goods'=仅商品相关
     */
    public function getTranslationTables(?string $scope = null): array
    {
        if ($scope === 'goods') {
            return array_intersect_key(
                $this->translationTables,
                array_flip(self::GOODS_TABLES)
            );
        }

        return $this->translationTables;
    }

    /**
     * 翻译单个表（公共方法，供分批翻译使用）
     * @param int $limit 每批处理的记录数，0 表示处理全部
     * @param int $offset 跳过的记录数
     */
    public function translateSingleTable(
        string $table,
        array $config,
        string $newLocale,
        string $sourceLocale,
        int $limit = 0,
        int $offset = 0
    ): array {
        // 检查表是否存在
        if (!$this->tableExists($table)) {
            return [
                'total' => 0,
                'translated' => 0,
                'failed' => 0,
                'skipped' => 0,
                'has_more' => false,
                'error' => 'Table does not exist',
            ];
        }

        return $this->translateTableBatch($table, $config, $newLocale, $sourceLocale, $limit, $offset);
    }

    /**
     * 分批翻译单个表（使用批量翻译 API）
     */
    protected function translateTableBatch(
        string $table,
        array $config,
        string $newLocale,
        string $sourceLocale,
        int $limit = 0,
        int $offset = 0
    ): array {
        $foreignKey = $config['foreign_key'];
        $fields = $config['fields'];
        $hasExtraFields = isset($config['has_extra_fields']) ? $config['has_extra_fields'] : false;

        // 使用 NOT EXISTS 子查询排除已翻译的记录（比 whereNotIn 高效，避免加载全量 ID 到内存）
        $notExistsRaw = "NOT EXISTS (SELECT 1 FROM {$table} tgt WHERE tgt.locale = ? AND tgt.{$foreignKey} = {$table}.{$foreignKey})";

        // 获取总数（还需要翻译的数量）
        $total = Db::table($table)
            ->where('locale', $sourceLocale)
            ->whereRaw($notExistsRaw, [$newLocale])
            ->count();

        // 分批处理：始终从头取（因为已翻译的已被 NOT EXISTS 排除）
        $query = Db::table($table)
            ->where('locale', $sourceLocale)
            ->whereRaw($notExistsRaw, [$newLocale]);

        if ($limit > 0) {
            $sourceRecords = $query->limit($limit)->select()->toArray();
        } else {
            $sourceRecords = $query->select()->toArray();
        }

        $result = [
            'total' => $total,
            'translated' => 0,
            'failed' => 0,
            'skipped' => 0,
            'processed' => count($sourceRecords),
            'has_more' => $limit > 0 && count($sourceRecords) < $total,
            'next_offset' => $offset + count($sourceRecords),
        ];

        if (empty($sourceRecords)) {
            $result['has_more'] = false;
            return $result;
        }

        // 品牌属性的选项不需要翻译（品牌名是专有名词）
        $skipOptionIds = [];
        if ($table === 'attribute_option_translations') {
            $brandAttrIds = Db::table('category_attributes')
                ->where('attr_key', 'brand')
                ->column('id');
            if (!empty($brandAttrIds)) {
                $skipOptionIds = Db::table('attribute_options')
                    ->whereIn('attribute_id', $brandAttrIds)
                    ->column('id');
            }
        }

        // 第一步：收集所有需要翻译的文本（普通文本 + HTML 文本节点统一收集）
        $textsToTranslate = [];  // [key => text] 所有需要翻译的文本
        $htmlStructures = [];    // [entityId:field => ['html' => ..., 'nodes' => [nodeKey => text]]]

        foreach ($sourceRecords as $record) {
            $entityId = $record[$foreignKey];

            // 跳过品牌选项（直接复制原文）
            if (!empty($skipOptionIds) && in_array($entityId, $skipOptionIds)) {
                continue;
            }

            foreach ($fields as $field) {
                if (isset($record[$field]) && !empty($record[$field])) {
                    $text = $record[$field];
                    $key = $entityId . ':' . $field;
                    if ($this->isHtml($text)) {
                        // HTML：提取文本节点，加入统一翻译队列
                        $nodes = $this->extractHtmlTextNodes($text);
                        if (!empty($nodes)) {
                            $htmlStructures[$key] = ['html' => $text, 'nodes' => []];
                            foreach ($nodes as $nodeIndex => $nodeText) {
                                $nodeKey = $key . ':node' . $nodeIndex;
                                $textsToTranslate[$nodeKey] = $nodeText;
                                $htmlStructures[$key]['nodes'][$nodeKey] = $nodeText;
                            }
                        }
                    } else {
                        $textsToTranslate[$key] = $text;
                    }
                }
            }
        }

        // 第二步：一次性批量翻译所有文本（普通文本 + HTML 文本节点）
        $translatedTexts = [];
        if (!empty($textsToTranslate)) {
            try {
                $translatedTexts = $this->translator->batchTranslate($textsToTranslate, $sourceLocale, $newLocale);
                Log::info("Batch translated " . count($textsToTranslate) . " texts for table {$table}");
            } catch (\Exception $e) {
                Log::error("Batch translation failed for table {$table}: " . $e->getMessage());
                $translatedTexts = $textsToTranslate;
            }
        }

        // 第三步：重组 HTML（用翻译后的文本节点替换原文）
        foreach ($htmlStructures as $key => $structure) {
            $html = $structure['html'];
            $nodeTranslations = [];
            foreach ($structure['nodes'] as $nodeKey => $originalText) {
                $nodeTranslations[$originalText] = $translatedTexts[$nodeKey] ?? $originalText;
            }
            $translatedTexts[$key] = $this->reassembleHtml($html, $nodeTranslations);
        }

        // 第四步：组装并插入翻译记录
        foreach ($sourceRecords as $record) {
            $entityId = $record[$foreignKey];

            try {
                // 基础字段
                $newRecord = [
                    $foreignKey => $entityId,
                    'locale' => $newLocale,
                ];

                // 只有支持额外字段的表才添加这些字段
                if ($hasExtraFields) {
                    $newRecord['is_original'] = 0;
                    $newRecord['is_auto_translated'] = 1;
                    $newRecord['translated_at'] = date('Y-m-d H:i:s');
                }

                foreach ($fields as $field) {
                    $key = $entityId . ':' . $field;
                    if (isset($translatedTexts[$key])) {
                        $translatedValue = $translatedTexts[$key];
                        $originalValue = $record[$field] ?? '';

                        // 验证翻译是否有效（不等于原文，且包含目标语言字符）
                        if ($translatedValue !== $originalValue && $this->containsTargetLanguageChars($translatedValue, $newLocale)) {
                            $newRecord[$field] = $translatedValue;
                        } else {
                            // 翻译无效，跳过此字段（使用原文）
                            $newRecord[$field] = $originalValue;
                        }
                    } else {
                        $newRecord[$field] = $record[$field] ?? '';
                    }
                }

                // 插入新记录
                Db::table($table)->insert($newRecord);
                $result['translated']++;

            } catch (\Exception $e) {
                Log::warning("Failed to insert record in {$table}, {$foreignKey}={$entityId}: " . $e->getMessage());
                $result['failed']++;
            }
        }

        return $result;
    }

    /**
     * 检查文本是否包含目标语言的字符
     */
    protected function containsTargetLanguageChars(string $text, string $locale): bool
    {
        // 各语言的字符范围正则表达式
        $patterns = [
            'ko-kr' => '/[\x{AC00}-\x{D7AF}]/u',  // 韩文
            'ja-jp' => '/[\x{3040}-\x{309F}\x{30A0}-\x{30FF}\x{4E00}-\x{9FAF}]/u',  // 日文（平假名、片假名、汉字）
            'zh-tw' => '/[\x{4E00}-\x{9FFF}]/u',  // 繁体中文
            'zh-cn' => '/[\x{4E00}-\x{9FFF}]/u',  // 简体中文
            'th-th' => '/[\x{0E00}-\x{0E7F}]/u',  // 泰文
            'vi-vn' => '/[àáạảãâầấậẩẫăằắặẳẵèéẹẻẽêềếệểễìíịỉĩòóọỏõôồốộổỗơờớợởỡùúụủũưừứựửữỳýỵỷỹđ]/iu',  // 越南文
            'ar-ae' => '/[\x{0600}-\x{06FF}]/u',  // 阿拉伯文
            'ru-ru' => '/[\x{0400}-\x{04FF}]/u',  // 俄文
        ];

        // 对于没有特殊字符检测的语言（如英语、法语等），返回 true（假设翻译有效）
        if (!isset($patterns[$locale])) {
            return true;
        }

        return preg_match($patterns[$locale], $text) === 1;
    }

    /**
     * 翻译单个表（内部方法，使用批量翻译）
     */
    protected function translateTable(
        string $table,
        array $config,
        string $newLocale,
        string $sourceLocale
    ): array {
        $result = [
            'total' => 0,
            'translated' => 0,
            'failed' => 0,
            'skipped' => 0,
        ];

        $foreignKey = $config['foreign_key'];
        $fields = $config['fields'];
        $hasExtraFields = isset($config['has_extra_fields']) ? $config['has_extra_fields'] : false;

        // 获取源语言的所有记录
        $sourceRecords = Db::table($table)
            ->where('locale', $sourceLocale)
            ->select()
            ->toArray();

        $result['total'] = count($sourceRecords);

        if (empty($sourceRecords)) {
            return $result;
        }

        // 获取已存在的新语言记录（避免重复）
        $existingIds = Db::table($table)
            ->where('locale', $newLocale)
            ->column($foreignKey);

        // 过滤出需要翻译的记录（排除已存在的）
        $recordsToTranslate = [];
        foreach ($sourceRecords as $record) {
            $entityId = $record[$foreignKey];
            if (in_array($entityId, $existingIds)) {
                $result['skipped']++;
            } else {
                $recordsToTranslate[] = $record;
            }
        }

        if (empty($recordsToTranslate)) {
            return $result;
        }

        // 第一步：收集所有需要翻译的文本
        $textsToTranslate = [];
        $htmlTexts = [];

        foreach ($recordsToTranslate as $record) {
            $entityId = $record[$foreignKey];
            foreach ($fields as $field) {
                if (isset($record[$field]) && !empty($record[$field])) {
                    $text = $record[$field];
                    $key = $entityId . ':' . $field;
                    if ($this->isHtml($text)) {
                        $htmlTexts[$key] = $text;
                    } else {
                        $textsToTranslate[$key] = $text;
                    }
                }
            }
        }

        // 第二步：批量翻译普通文本
        $translatedTexts = [];
        if (!empty($textsToTranslate)) {
            try {
                $translatedTexts = $this->translator->batchTranslate($textsToTranslate, $sourceLocale, $newLocale);
                Log::info("Batch translated " . count($textsToTranslate) . " texts for table {$table}");
            } catch (\Exception $e) {
                Log::error("Batch translation failed for table {$table}: " . $e->getMessage());
                $translatedTexts = $textsToTranslate;
            }
        }

        // 第三步：单独翻译 HTML 内容
        foreach ($htmlTexts as $key => $html) {
            try {
                $translatedTexts[$key] = $this->translateHtml($html, $sourceLocale, $newLocale);
            } catch (\Exception $e) {
                Log::warning("HTML translation failed for {$key}: " . $e->getMessage());
                $translatedTexts[$key] = $html;
            }
        }

        // 第四步：插入翻译记录
        foreach ($recordsToTranslate as $record) {
            $entityId = $record[$foreignKey];

            try {
                $newRecord = [
                    $foreignKey => $entityId,
                    'locale' => $newLocale,
                ];

                if ($hasExtraFields) {
                    $newRecord['is_original'] = 0;
                    $newRecord['is_auto_translated'] = 1;
                    $newRecord['translated_at'] = date('Y-m-d H:i:s');
                }

                foreach ($fields as $field) {
                    $key = $entityId . ':' . $field;
                    if (isset($translatedTexts[$key])) {
                        $translatedValue = $translatedTexts[$key];
                        $originalValue = $record[$field] ?? '';

                        if ($translatedValue !== $originalValue && $this->containsTargetLanguageChars($translatedValue, $newLocale)) {
                            $newRecord[$field] = $translatedValue;
                        } else {
                            $newRecord[$field] = $originalValue;
                        }
                    } else {
                        $newRecord[$field] = $record[$field] ?? '';
                    }
                }

                Db::table($table)->insert($newRecord);
                $result['translated']++;

            } catch (\Exception $e) {
                Log::warning("Failed to insert record in {$table}, {$foreignKey}={$entityId}: " . $e->getMessage());
                $result['failed']++;
            }
        }

        return $result;
    }

    /**
     * 检查是否为 HTML 内容
     */
    protected function isHtml(string $text): bool
    {
        return $text !== strip_tags($text);
    }

    /**
     * 从 HTML 中提取文本节点
     * @return array [index => text]
     */
    protected function extractHtmlTextNodes(string $html): array
    {
        $nodes = [];
        $pattern = '/>(.*?)</s';
        preg_match_all($pattern, $html, $matches);
        if (!empty($matches[1])) {
            foreach ($matches[1] as $index => $text) {
                $trimmed = trim($text);
                if (!empty($trimmed)) {
                    $nodes[$index] = $trimmed;
                }
            }
        }
        return $nodes;
    }

    /**
     * 用翻译后的文本重组 HTML
     * @param string $html 原始 HTML
     * @param array $translations [原文 => 译文]
     */
    protected function reassembleHtml(string $html, array $translations): string
    {
        $pattern = '/>(.*?)</s';
        return preg_replace_callback($pattern, function ($matches) use ($translations) {
            $text = trim($matches[1]);
            if (empty($text) || !isset($translations[$text])) {
                return '>' . $matches[1] . '<';
            }
            return '>' . $translations[$text] . '<';
        }, $html);
    }

    /**
     * 检查表是否存在
     */
    protected function tableExists(string $table): bool
    {
        try {
            Db::query("SELECT 1 FROM {$table} LIMIT 1");
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * 初始化翻译进度
     */
    public function initProgress(string $locale): void
    {
        $this->updateProgress($locale, [
            'status' => 'pending',
            'current_table' => '',
            'tables_done' => 0,
            'tables_total' => count($this->translationTables),
            'records_done' => 0,
            'records_total' => 0,
            'error' => null,
            'started_at' => date('Y-m-d H:i:s'),
        ]);
    }

    /**
     * 设置进度错误
     */
    public function setProgressError(string $locale, string $error): void
    {
        $this->updateProgress($locale, [
            'status' => 'error',
            'error' => $error,
            'finished_at' => date('Y-m-d H:i:s'),
        ]);
    }

    /**
     * 更新翻译进度
     */
    protected function updateProgress(string $locale, array $data): void
    {
        $key = self::PROGRESS_CACHE_PREFIX . $locale;
        $current = Cache::get($key, []);
        $merged = array_merge($current, $data, ['updated_at' => date('Y-m-d H:i:s')]);
        Cache::set($key, $merged, 3600); // 1小时过期
    }

    /**
     * 获取翻译进度
     */
    public function getProgress(string $locale): array
    {
        $key = self::PROGRESS_CACHE_PREFIX . $locale;
        return Cache::get($key, [
            'status' => 'not_started',
            'current_table' => '',
            'tables_done' => 0,
            'tables_total' => count($this->translationTables),
            'records_done' => 0,
            'records_total' => 0,
        ]);
    }

    /**
     * 清除进度缓存
     */
    public function clearProgress(string $locale): void
    {
        $key = self::PROGRESS_CACHE_PREFIX . $locale;
        Cache::delete($key);
    }

    /**
     * 获取翻译表统计
     */
    public function getTranslationStats(string $locale): array
    {
        $stats = [];
        foreach ($this->translationTables as $table => $config) {
            if (!$this->tableExists($table)) {
                continue;
            }

            $total = Db::table($table)->where('locale', $locale)->count();
            $autoTranslated = 0;

            // 只有支持额外字段的表才统计自动翻译数量
            $hasExtraFields = isset($config['has_extra_fields']) ? $config['has_extra_fields'] : false;
            if ($hasExtraFields) {
                $autoTranslated = Db::table($table)
                    ->where('locale', $locale)
                    ->where('is_auto_translated', 1)
                    ->count();
            }

            $stats[$table] = [
                'total' => $total,
                'auto_translated' => $autoTranslated,
            ];
        }
        return $stats;
    }

    /**
     * 删除语言的翻译
     * @param string|null $scope null=全部, 'goods'=仅商品相关
     */
    public function deleteTranslationsForLanguage(string $locale, ?string $scope = null): array
    {
        $tables = $this->getTranslationTables($scope);
        $results = [];
        foreach ($tables as $table => $config) {
            if (!$this->tableExists($table)) {
                continue;
            }
            $deleted = Db::table($table)->where('locale', $locale)->delete();
            $results[$table] = $deleted;
        }
        return $results;
    }

    /**
     * 删除指定表的指定语言翻译
     */
    public function deleteTranslationsForTable(string $table, string $locale): int
    {
        if (!$this->tableExists($table)) {
            return 0;
        }
        return Db::table($table)->where('locale', $locale)->delete();
    }
}
