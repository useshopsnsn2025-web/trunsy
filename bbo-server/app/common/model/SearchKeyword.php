<?php
declare(strict_types=1);

namespace app\common\model;

use think\Model;

/**
 * 搜索关键词统计模型
 */
class SearchKeyword extends Model
{
    /**
     * 表名
     * @var string
     */
    protected $name = 'search_keywords';

    /**
     * 主键
     * @var string
     */
    protected $pk = 'id';

    /**
     * 自动时间戳
     * @var bool|string
     */
    protected $autoWriteTimestamp = 'datetime';

    /**
     * 创建时间字段
     * @var string
     */
    protected $createTime = 'created_at';

    /**
     * 更新时间字段
     * @var string
     */
    protected $updateTime = 'updated_at';

    /**
     * 类型转换
     * @var array
     */
    protected $type = [
        'search_count' => 'integer',
        'result_count' => 'integer',
    ];

    /**
     * 记录搜索关键词
     * @param string $keyword 搜索关键词
     * @param int $resultCount 搜索结果数量
     * @param string $locale 用户语言
     * @return void
     */
    public static function recordKeyword(string $keyword, int $resultCount = 0, string $locale = 'en-us'): void
    {
        $keyword = trim($keyword);
        if (empty($keyword) || mb_strlen($keyword) > 100) {
            return;
        }

        // 标准化语言代码
        $locale = strtolower($locale);

        try {
            $record = self::where('keyword', $keyword)
                ->where('locale', $locale)
                ->find();

            if ($record) {
                // 更新现有记录
                $record->search_count = $record->search_count + 1;
                $record->result_count = $resultCount;
                $record->last_searched_at = date('Y-m-d H:i:s');
                $record->save();
            } else {
                // 创建新记录
                $newRecord = new self();
                $newRecord->keyword = $keyword;
                $newRecord->locale = $locale;
                $newRecord->search_count = 1;
                $newRecord->result_count = $resultCount;
                $newRecord->last_searched_at = date('Y-m-d H:i:s');
                $newRecord->save();
            }
        } catch (\Exception $e) {
            // 忽略错误，不影响主业务
            \think\facade\Log::error('Failed to record search keyword: ' . $e->getMessage());
        }
    }

    /**
     * 获取热门搜索词
     * @param int $limit 返回数量
     * @param int $days 统计天数（0表示全部时间）
     * @param string $locale 用户语言
     * @return array
     */
    public static function getHotKeywords(int $limit = 10, int $days = 7, string $locale = 'en-us'): array
    {
        $locale = strtolower($locale);

        // 获取同语系的语言列表
        $localeFamily = self::getLocaleFamily($locale);

        $query = self::field('keyword, search_count')
            ->where('search_count', '>', 0)
            ->whereIn('locale', $localeFamily)
            ->order('search_count', 'desc')
            ->order('last_searched_at', 'desc');

        // 如果指定了天数，只统计最近N天的
        if ($days > 0) {
            $startDate = date('Y-m-d H:i:s', strtotime("-{$days} days"));
            $query->where('last_searched_at', '>=', $startDate);
        }

        $results = $query->limit($limit)->select();
        $keywords = array_column($results->toArray(), 'keyword');

        // 如果当前语言热词不足，补充通用热词（英语）
        if (count($keywords) < $limit && !in_array('en-us', $localeFamily)) {
            $remaining = $limit - count($keywords);
            $fallbackQuery = self::field('keyword, search_count')
                ->where('search_count', '>', 0)
                ->where('locale', 'en-us')
                ->whereNotIn('keyword', $keywords ?: [''])
                ->order('search_count', 'desc')
                ->order('last_searched_at', 'desc');

            if ($days > 0) {
                $startDate = date('Y-m-d H:i:s', strtotime("-{$days} days"));
                $fallbackQuery->where('last_searched_at', '>=', $startDate);
            }

            $fallbackResults = $fallbackQuery->limit($remaining)->select();
            $fallbackKeywords = array_column($fallbackResults->toArray(), 'keyword');
            $keywords = array_merge($keywords, $fallbackKeywords);
        }

        return $keywords;
    }

    /**
     * 获取同语系的语言列表
     * @param string $locale
     * @return array
     */
    private static function getLocaleFamily(string $locale): array
    {
        // 中文语系（简体、繁体香港、繁体台湾）
        $chineseFamily = ['zh-cn', 'zh-hk', 'zh-tw'];

        if (in_array($locale, $chineseFamily)) {
            return $chineseFamily;
        }

        // 其他语言只返回自身
        return [$locale];
    }

    /**
     * 获取搜索建议
     * @param string $keyword 输入的关键词
     * @param int $limit 返回数量
     * @param string $locale 用户语言
     * @return array
     */
    public static function getSuggestions(string $keyword, int $limit = 10, string $locale = 'en-us'): array
    {
        $keyword = trim($keyword);
        if (empty($keyword)) {
            return [];
        }

        $locale = strtolower($locale);
        $localeFamily = self::getLocaleFamily($locale);

        // 优先搜索同语系的建议
        $results = self::field('keyword, search_count')
            ->where('keyword', 'like', "%{$keyword}%")
            ->where('search_count', '>', 0)
            ->whereIn('locale', $localeFamily)
            ->order('search_count', 'desc')
            ->limit($limit)
            ->select();

        $suggestions = array_column($results->toArray(), 'keyword');

        // 如果建议不足，补充其他语言的建议
        if (count($suggestions) < $limit) {
            $remaining = $limit - count($suggestions);
            $fallbackResults = self::field('keyword, search_count')
                ->where('keyword', 'like', "%{$keyword}%")
                ->where('search_count', '>', 0)
                ->whereNotIn('locale', $localeFamily)
                ->whereNotIn('keyword', $suggestions ?: [''])
                ->order('search_count', 'desc')
                ->limit($remaining)
                ->select();

            $fallbackSuggestions = array_column($fallbackResults->toArray(), 'keyword');
            $suggestions = array_merge($suggestions, $fallbackSuggestions);
        }

        return $suggestions;
    }
}
