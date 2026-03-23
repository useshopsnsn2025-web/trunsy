<?php
declare(strict_types=1);

namespace app\admin\controller;

use think\Response;
use think\facade\Cache as CacheFacade;

/**
 * 缓存管理控制器
 */
class Cache extends Base
{
    /**
     * 缓存类型
     */
    const CACHE_TYPES = [
        'all' => '全部缓存',
        'data' => '数据缓存',
        'view' => '视图缓存',
        'route' => '路由缓存',
        'config' => '配置缓存',
    ];

    /**
     * 获取缓存状态
     */
    public function status(): Response
    {
        $cacheDir = runtime_path() . 'cache';
        $tempDir = runtime_path() . 'temp';
        $logDir = runtime_path() . 'log';

        return $this->success([
            'cache_size' => $this->getDirSize($cacheDir),
            'cache_size_text' => $this->formatSize($this->getDirSize($cacheDir)),
            'temp_size' => $this->getDirSize($tempDir),
            'temp_size_text' => $this->formatSize($this->getDirSize($tempDir)),
            'log_size' => $this->getDirSize($logDir),
            'log_size_text' => $this->formatSize($this->getDirSize($logDir)),
            'cache_types' => self::CACHE_TYPES,
        ]);
    }

    /**
     * 清理缓存
     */
    public function clear(): Response
    {
        $type = input('post.type', 'all');

        $cleared = [];

        switch ($type) {
            case 'data':
                // 清理数据缓存
                CacheFacade::clear();
                $cleared[] = '数据缓存';
                break;

            case 'view':
                // 清理视图缓存
                $this->clearDir(runtime_path() . 'temp');
                $cleared[] = '视图缓存';
                break;

            case 'route':
                // 清理路由缓存
                $routeCache = runtime_path() . 'route.php';
                if (is_file($routeCache)) {
                    unlink($routeCache);
                }
                $cleared[] = '路由缓存';
                break;

            case 'config':
                // 清理配置缓存
                $this->clearDir(runtime_path() . 'cache');
                $cleared[] = '配置缓存';
                break;

            case 'log':
                // 清理日志文件
                $this->clearDir(runtime_path() . 'log');
                $cleared[] = '日志文件';
                break;

            case 'all':
            default:
                // 清理所有缓存
                CacheFacade::clear();
                $this->clearDir(runtime_path() . 'cache');
                $this->clearDir(runtime_path() . 'temp');
                $routeCache = runtime_path() . 'route.php';
                if (is_file($routeCache)) {
                    unlink($routeCache);
                }
                $cleared = ['数据缓存', '视图缓存', '路由缓存', '配置缓存'];
                break;
        }

        return $this->success([
            'cleared' => $cleared,
        ], '缓存清理成功：' . implode('、', $cleared));
    }

    /**
     * 清理日志
     */
    public function clearLogs(): Response
    {
        $days = input('post.days', 0); // 保留最近几天，0表示全部清理

        $logDir = runtime_path() . 'log';
        $clearedCount = 0;
        $clearedSize = 0;

        if (is_dir($logDir)) {
            $this->clearLogFiles($logDir, $days, $clearedCount, $clearedSize);
        }

        return $this->success([
            'cleared_count' => $clearedCount,
            'cleared_size' => $clearedSize,
            'cleared_size_text' => $this->formatSize($clearedSize),
        ], "已清理 {$clearedCount} 个日志文件，释放 " . $this->formatSize($clearedSize));
    }

    /**
     * 获取目录大小
     */
    private function getDirSize(string $dir): int
    {
        $size = 0;
        if (!is_dir($dir)) {
            return $size;
        }

        $files = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \RecursiveDirectoryIterator::SKIP_DOTS)
        );

        foreach ($files as $file) {
            $size += $file->getSize();
        }

        return $size;
    }

    /**
     * 清理目录
     */
    private function clearDir(string $dir): bool
    {
        if (!is_dir($dir)) {
            return true;
        }

        $files = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($files as $file) {
            if ($file->isDir()) {
                @rmdir($file->getRealPath());
            } else {
                @unlink($file->getRealPath());
            }
        }

        return true;
    }

    /**
     * 清理日志文件
     */
    private function clearLogFiles(string $dir, int $keepDays, int &$count, int &$size): void
    {
        if (!is_dir($dir)) {
            return;
        }

        $cutoffTime = $keepDays > 0 ? strtotime("-{$keepDays} days") : PHP_INT_MAX;

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($iterator as $file) {
            if ($file->isFile() && $file->getMTime() < $cutoffTime) {
                $fileSize = $file->getSize();
                if (@unlink($file->getRealPath())) {
                    $count++;
                    $size += $fileSize;
                }
            } elseif ($file->isDir()) {
                // 尝试删除空目录
                @rmdir($file->getRealPath());
            }
        }
    }

    /**
     * 格式化文件大小
     */
    private function formatSize(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $index = 0;
        $size = (float)$bytes;

        while ($size >= 1024 && $index < count($units) - 1) {
            $size /= 1024;
            $index++;
        }

        return round($size, 2) . ' ' . $units[$index];
    }
}
