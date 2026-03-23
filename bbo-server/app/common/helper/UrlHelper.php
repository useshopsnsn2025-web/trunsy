<?php
declare(strict_types=1);

namespace app\common\helper;

/**
 * URL 辅助工具类
 * 用于处理图片等资源 URL 的动态域名转换
 */
class UrlHelper
{
    /**
     * 本地存储路径标识
     * 包含这些路径的 URL 被视为本地存储，需要转换域名
     * @var array
     */
    protected static $localStoragePaths = [
        '/storage/',
    ];

    /**
     * 判断是否为本地存储的 URL（需要转换）
     * 判断逻辑：URL 路径包含本地存储标识（如 /storage/）
     *
     * @param string $path URL 路径
     * @return bool
     */
    protected static function isLocalStoragePath(string $path): bool
    {
        foreach (self::$localStoragePaths as $storagePath) {
            if (strpos($path, $storagePath) !== false) {
                return true;
            }
        }
        return false;
    }

    /**
     * 转换图片 URL 为当前请求域名
     * 仅转换本地存储的 URL（路径包含 /storage/），第三方链接保持不变
     *
     * @param string|null $url 原始 URL
     * @return string|null 转换后的 URL
     */
    public static function convertImageUrl(?string $url): ?string
    {
        if (empty($url)) {
            return $url;
        }

        // 如果不是完整 URL（相对路径），转为完整 URL
        if (!preg_match('/^https?:\/\//i', $url)) {
            return self::getFullUrl($url);
        }

        // 解析原始 URL
        $parsed = parse_url($url);
        if (!$parsed || !isset($parsed['host'])) {
            return $url;
        }

        $path = $parsed['path'] ?? '';

        // 只转换本地存储路径的 URL，第三方链接保持不变
        if (!self::isLocalStoragePath($path)) {
            return $url;
        }

        // 获取当前请求的域名
        $currentDomain = request()->domain();

        // 如果域名已经一致，无需转换
        $currentHost = parse_url($currentDomain, PHP_URL_HOST);
        if ($currentHost && strcasecmp($parsed['host'], $currentHost) === 0) {
            return $url;
        }

        // 构建新 URL：使用当前域名 + 原始路径
        $query = isset($parsed['query']) ? '?' . $parsed['query'] : '';
        $fragment = isset($parsed['fragment']) ? '#' . $parsed['fragment'] : '';

        return $currentDomain . $path . $query . $fragment;
    }

    /**
     * 批量转换图片 URL
     *
     * @param array $urls URL 数组
     * @return array 转换后的 URL 数组
     */
    public static function convertImageUrls(array $urls): array
    {
        return array_map([self::class, 'convertImageUrl'], $urls);
    }

    /**
     * 转换数组中指定字段的图片 URL
     *
     * @param array $data 数据数组
     * @param array $fields 需要转换的字段名列表
     * @return array 转换后的数据
     */
    public static function convertFieldUrls(array $data, array $fields): array
    {
        foreach ($fields as $field) {
            if (isset($data[$field])) {
                if (is_array($data[$field])) {
                    $data[$field] = self::convertImageUrls($data[$field]);
                } else {
                    $data[$field] = self::convertImageUrl($data[$field]);
                }
            }
        }
        return $data;
    }

    /**
     * 批量转换列表中指定字段的图片 URL
     *
     * @param array $list 数据列表
     * @param array $fields 需要转换的字段名列表
     * @return array 转换后的列表
     */
    public static function convertListFieldUrls(array $list, array $fields): array
    {
        return array_map(function ($item) use ($fields) {
            return self::convertFieldUrls($item, $fields);
        }, $list);
    }

    /**
     * 将相对路径转换为完整 URL
     * 如果是完整 URL（以 http:// 或 https:// 开头），则进行域名转换检查
     *
     * @param string|null $path 相对路径或完整 URL
     * @return string|null 完整 URL
     */
    public static function getFullUrl(?string $path): ?string
    {
        if (empty($path)) {
            return $path;
        }

        // 如果已经是完整 URL，进行域名转换检查
        if (preg_match('/^https?:\/\//i', $path)) {
            return self::convertImageUrl($path);
        }

        // 统一路径分隔符（Windows 兼容）
        $path = str_replace('\\', '/', $path);

        // 获取当前请求的域名
        $domain = request()->domain();

        // 确保路径以 / 开头
        if (!str_starts_with($path, '/')) {
            $path = '/' . $path;
        }

        // 如果路径不包含 /storage/，则添加 /storage 前缀
        // 这是因为本地上传的文件存储在 public/storage/ 目录下
        if (strpos($path, '/storage/') === false) {
            $path = '/storage' . $path;
        }

        return $domain . $path;
    }
}
