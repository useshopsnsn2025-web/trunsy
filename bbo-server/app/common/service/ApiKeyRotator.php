<?php
declare(strict_types=1);

namespace app\common\service;

use think\facade\Cache;

/**
 * API Key 轮换器
 * 支持多 key 轮流使用，当一个 key 配额耗尽时自动切换到下一个
 */
class ApiKeyRotator
{
    /**
     * 配置键名（如 scraper_api_key、gemini_api_key）
     */
    protected $configKey;

    /**
     * 所有可用的 key 列表
     */
    protected $keys = [];

    /**
     * 冷却时间（秒），key 失败后多久重新启用
     */
    protected $cooldownSeconds = 3600;

    /**
     * @param string $configKey   system_configs 表中的 key 名
     * @param int    $cooldown    失败 key 的冷却时间（秒），默认1小时
     */
    public function __construct($configKey, $cooldown = 3600)
    {
        $this->configKey = $configKey;
        $this->cooldownSeconds = $cooldown;
        $this->loadKeys();
    }

    /**
     * 从数据库加载 key 列表
     */
    protected function loadKeys()
    {
        try {
            $config = \think\facade\Db::table('system_configs')
                ->where('key', $this->configKey)
                ->find();
            $value = $config['value'] ?? '';
            if (!empty($value)) {
                // 支持换行、逗号、分号分隔
                $keys = preg_split('/[\r\n,;]+/', $value);
                $this->keys = array_values(array_filter(array_map('trim', $keys)));
            }
        } catch (\Exception $e) {
            // 忽略
        }
    }

    /**
     * 获取当前可用的 key
     * 优先返回未被标记失败的 key，按轮换顺序
     *
     * @return string|null 可用的 key，无可用时返回 null
     */
    public function getKey()
    {
        if (empty($this->keys)) {
            return null;
        }

        $failedKeys = $this->getFailedKeys();
        $index = $this->getRotationIndex();

        // 从当前索引开始，找到第一个未失败的 key
        $count = count($this->keys);
        for ($i = 0; $i < $count; $i++) {
            $idx = ($index + $i) % $count;
            $key = $this->keys[$idx];
            if (!isset($failedKeys[$key]) || $failedKeys[$key] < time()) {
                // 更新轮换索引到下一个位置
                $this->setRotationIndex(($idx + 1) % $count);
                return $key;
            }
        }

        // 所有 key 都在冷却中，返回第一个（让它尝试，可能已恢复）
        $this->setRotationIndex(1 % $count);
        return $this->keys[0];
    }

    /**
     * 标记 key 为失败（配额耗尽等）
     * key 将在冷却时间后自动恢复
     *
     * @param string $key 失败的 key
     */
    public function markFailed($key)
    {
        $failedKeys = $this->getFailedKeys();
        $failedKeys[$key] = time() + $this->cooldownSeconds;
        Cache::set($this->getCacheKey('failed'), $failedKeys, $this->cooldownSeconds * 2);

        $this->log("Key marked as failed (cooldown {$this->cooldownSeconds}s): " . substr($key, 0, 8) . '...');
    }

    /**
     * 清除所有失败标记
     */
    public function clearFailed()
    {
        Cache::delete($this->getCacheKey('failed'));
    }

    /**
     * 获取所有 key 的数量
     */
    public function getKeyCount()
    {
        return count($this->keys);
    }

    /**
     * 检查是否有可用的 key
     */
    public function hasKeys()
    {
        return !empty($this->keys);
    }

    /**
     * 获取失败 key 列表
     */
    protected function getFailedKeys()
    {
        try {
            return Cache::get($this->getCacheKey('failed'), []);
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * 获取当前轮换索引
     */
    protected function getRotationIndex()
    {
        try {
            return (int) Cache::get($this->getCacheKey('index'), 0);
        } catch (\Exception $e) {
            return 0;
        }
    }

    /**
     * 设置轮换索引
     */
    protected function setRotationIndex($index)
    {
        try {
            Cache::set($this->getCacheKey('index'), $index, 86400);
        } catch (\Exception $e) {
            // 忽略
        }
    }

    /**
     * 生成缓存键
     */
    protected function getCacheKey($suffix)
    {
        return 'api_key_rotator_' . $this->configKey . '_' . $suffix;
    }

    /**
     * 日志
     */
    protected function log($message)
    {
        try {
            \think\facade\Log::info("[ApiKeyRotator:{$this->configKey}] {$message}");
        } catch (\Exception $e) {
            // 忽略
        }
    }
}
