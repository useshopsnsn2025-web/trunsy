<?php
declare(strict_types=1);

namespace app\common\model;

use think\Model;
use think\facade\Db;

/**
 * UI 翻译模型
 * 存储前端界面的多语言翻译
 */
class UiTranslation extends Model
{
    protected $table = 'ui_translations';
    protected $autoWriteTimestamp = true;
    protected $createTime = 'created_at';
    protected $updateTime = 'updated_at';

    /**
     * 获取指定语言的所有翻译（按命名空间分组）
     */
    public static function getTranslationsByLocale(string $locale): array
    {
        $translations = self::where('locale', $locale)
            ->field('namespace, `key`, value')
            ->select()
            ->toArray();

        $result = [];
        foreach ($translations as $item) {
            if (!isset($result[$item['namespace']])) {
                $result[$item['namespace']] = [];
            }
            // 支持嵌套键，如 login.title
            self::setNestedValue($result[$item['namespace']], $item['key'], $item['value']);
        }

        return $result;
    }

    /**
     * 获取指定语言和命名空间的翻译
     */
    public static function getTranslationsByNamespace(string $locale, string $namespace): array
    {
        $translations = self::where('locale', $locale)
            ->where('namespace', $namespace)
            ->field('`key`, value')
            ->select()
            ->toArray();

        $result = [];
        foreach ($translations as $item) {
            self::setNestedValue($result, $item['key'], $item['value']);
        }

        return $result;
    }

    /**
     * 批量导入翻译
     */
    public static function importTranslations(string $locale, string $namespace, array $translations, bool $isOriginal = false): int
    {
        $count = 0;
        $flattenedData = self::flattenArray($translations);
        $now = date('Y-m-d H:i:s');

        foreach ($flattenedData as $key => $value) {
            if (!is_string($value)) {
                continue;
            }

            $existing = self::where('locale', $locale)
                ->where('namespace', $namespace)
                ->where('key', $key)
                ->find();

            if ($existing) {
                $existing->value = $value;
                $existing->save();
            } else {
                self::create([
                    'locale' => $locale,
                    'namespace' => $namespace,
                    'key' => $key,
                    'value' => $value,
                    'is_original' => $isOriginal ? 1 : 0,
                    'is_auto_translated' => 0,
                    'created_at' => $now,
                ]);
            }
            $count++;
        }

        return $count;
    }

    /**
     * 为新语言生成翻译（从英语翻译）- 支持分批处理和批量翻译
     * @param string $targetLocale 目标语言
     * @param string $sourceLocale 源语言
     * @param int $limit 每批处理数量，0表示全部
     * @param int $offset 跳过数量
     * @return array
     */
    public static function generateTranslationsForLocale(string $targetLocale, string $sourceLocale = 'en-us', int $limit = 0, int $offset = 0): array
    {
        $translateService = new \app\common\service\TranslateService();
        if (!$translateService->isConfigured()) {
            return ['error' => 'Translation API not configured'];
        }

        // 获取目标语言已有的翻译键（用于排除）
        $existingRows = self::where('locale', $targetLocale)
            ->field('namespace, `key`')
            ->select();
        $existingKeys = [];
        foreach ($existingRows as $row) {
            $existingKeys[] = $row->namespace . ':' . $row->getData('key');
        }

        // 获取源语言需要翻译的记录（排除已存在的）
        $allSource = self::where('locale', $sourceLocale)->select()->toArray();
        $needTranslate = [];
        foreach ($allSource as $item) {
            $compositeKey = $item['namespace'] . ':' . $item['key'];
            if (!in_array($compositeKey, $existingKeys)) {
                $needTranslate[] = $item;
            }
        }

        $total = count($needTranslate);

        // 分批处理：始终从头取（因为每次查询都会排除已翻译的记录）
        if ($limit > 0) {
            $needTranslate = array_slice($needTranslate, 0, $limit);
        }

        $results = [
            'total' => $total,
            'translated' => 0,
            'skipped' => 0,
            'failed' => 0,
            'processed' => count($needTranslate),
            'has_more' => $limit > 0 && count($needTranslate) < $total,
            'next_offset' => $offset + count($needTranslate),  // 保留用于前端进度计算
        ];

        if (empty($needTranslate)) {
            $results['has_more'] = false;
            return $results;
        }

        $now = date('Y-m-d H:i:s');

        // 准备批量翻译数据
        $textsToTranslate = [];
        $sourceMap = []; // 保存源数据映射

        foreach ($needTranslate as $source) {
            $sourceValue = $source['value'];
            $compositeKey = $source['namespace'] . ':' . $source['key'];

            // 空值或过短的文本直接按原文保存（不发送翻译API）
            if (empty($sourceValue) || mb_strlen($sourceValue) < 2) {
                try {
                    self::create([
                        'locale' => $targetLocale,
                        'namespace' => $source['namespace'],
                        'key' => $source['key'],
                        'value' => $sourceValue ?? '',
                        'is_original' => 0,
                        'is_auto_translated' => 1,
                        'translated_at' => $now,
                        'created_at' => $now,
                    ]);
                    $results['translated']++;
                } catch (\Exception $e) {
                    $results['failed']++;
                }
                continue;
            }

            $textsToTranslate[$compositeKey] = $sourceValue;
            $sourceMap[$compositeKey] = $source;
        }

        if (empty($textsToTranslate)) {
            return $results;
        }

        // 批量翻译
        try {
            $translatedTexts = $translateService->batchTranslate($textsToTranslate, $sourceLocale, $targetLocale);

            foreach ($translatedTexts as $compositeKey => $translatedValue) {
                $source = $sourceMap[$compositeKey];
                $sourceValue = $textsToTranslate[$compositeKey];

                // 检查翻译质量
                // 对于使用拉丁字母的语言（如印尼语、马来语），翻译结果可能与英文相同，这是正常的
                $isLatinLocale = self::isLatinLocale($targetLocale);
                $isLowQuality = !$isLatinLocale && ($translatedValue === $sourceValue || !self::containsTargetLanguageChars($translatedValue, $targetLocale));

                if ($isLowQuality) {
                    \think\facade\Log::info("Low quality translation for {$source['namespace']}.{$source['key']}: {$sourceValue} -> {$translatedValue}");
                }

                // 无论翻译质量如何，都保存到数据库，避免重复处理
                try {
                    self::create([
                        'locale' => $targetLocale,
                        'namespace' => $source['namespace'],
                        'key' => $source['key'],
                        'value' => $translatedValue,
                        'is_original' => 0,
                        'is_auto_translated' => 1,
                        'translated_at' => $now,
                        'created_at' => $now,
                    ]);
                    if ($isLowQuality) {
                        $results['skipped']++;
                    } else {
                        $results['translated']++;
                    }
                } catch (\Exception $e) {
                    \think\facade\Log::warning("Failed to save translation for {$source['namespace']}.{$source['key']}: " . $e->getMessage());
                    $results['failed']++;
                }
            }
        } catch (\Exception $e) {
            \think\facade\Log::error("Batch translation failed: " . $e->getMessage());
            $results['failed'] = count($textsToTranslate);
        }

        return $results;
    }

    /**
     * 删除指定语言的所有翻译
     */
    public static function deleteByLocale(string $locale): int
    {
        return self::where('locale', $locale)->delete();
    }

    /**
     * 获取所有有翻译数据的语言代码
     */
    public static function getAvailableLocales(): array
    {
        return self::distinct(true)
            ->column('locale');
    }

    /**
     * 获取翻译统计
     */
    public static function getStats(string $locale): array
    {
        $total = self::where('locale', $locale)->count();
        $autoTranslated = self::where('locale', $locale)
            ->where('is_auto_translated', 1)
            ->count();

        $namespaces = self::where('locale', $locale)
            ->group('namespace')
            ->column('count(*) as count', 'namespace');

        return [
            'total' => $total,
            'auto_translated' => $autoTranslated,
            'manual' => $total - $autoTranslated,
            'namespaces' => $namespaces,
        ];
    }

    /**
     * 检查目标语言是否使用拉丁字母（翻译结果可能与英文相同）
     */
    private static function isLatinLocale(string $locale): bool
    {
        $latinLocales = [
            'id-id', // 印尼语
            'ms-my', // 马来语
            'tl-ph', // 菲律宾语
            'fr-fr', // 法语
            'es-es', // 西班牙语
            'pt-br', // 葡萄牙语
            'de-de', // 德语
            'it-it', // 意大利语
            'nl-nl', // 荷兰语
            'tr-tr', // 土耳其语
            'pl-pl', // 波兰语
            'ro-ro', // 罗马尼亚语
            'hu-hu', // 匈牙利语
            'cs-cz', // 捷克语
            'sv-se', // 瑞典语
            'da-dk', // 丹麦语
            'fi-fi', // 芬兰语
            'nb-no', // 挪威语
        ];
        return in_array($locale, $latinLocales);
    }

    /**
     * 检查文本是否包含目标语言的字符
     */
    private static function containsTargetLanguageChars(string $text, string $locale): bool
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
     * 设置嵌套数组值
     */
    private static function setNestedValue(array &$array, string $key, $value): void
    {
        $keys = explode('.', $key);
        $current = &$array;

        foreach ($keys as $k) {
            if (!isset($current[$k]) || !is_array($current[$k])) {
                $current[$k] = [];
            }
            $current = &$current[$k];
        }

        $current = $value;
    }

    /**
     * 扁平化嵌套数组
     */
    private static function flattenArray(array $array, string $prefix = ''): array
    {
        $result = [];

        foreach ($array as $key => $value) {
            $newKey = $prefix ? $prefix . '.' . $key : $key;

            if (is_array($value)) {
                $result = array_merge($result, self::flattenArray($value, $newKey));
            } else {
                $result[$newKey] = $value;
            }
        }

        return $result;
    }
}
