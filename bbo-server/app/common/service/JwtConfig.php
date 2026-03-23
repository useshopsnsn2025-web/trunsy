<?php
declare(strict_types=1);

namespace app\common\service;

use app\common\model\SystemConfig;

/**
 * JWT 配置服务
 * 从数据库读取 JWT 配置，支持后台动态配置
 */
class JwtConfig
{
    /**
     * 缓存的配置
     * @var array|null
     */
    protected static $config = null;

    /**
     * 默认配置值
     */
    const DEFAULT_TTL = 7200;           // 2小时
    const DEFAULT_REFRESH_TTL = 604800; // 7天

    /**
     * 获取 Token 有效期（秒）
     * @return int
     */
    public static function getTtl(): int
    {
        self::loadConfig();
        return self::$config['ttl'] ?? self::DEFAULT_TTL;
    }

    /**
     * 获取刷新 Token 有效期（秒）
     * @return int
     */
    public static function getRefreshTtl(): int
    {
        self::loadConfig();
        return self::$config['refresh_ttl'] ?? self::DEFAULT_REFRESH_TTL;
    }

    /**
     * 加载配置
     * 优先从数据库读取，降级到 .env
     */
    protected static function loadConfig(): void
    {
        if (self::$config !== null) {
            return;
        }

        try {
            // 尝试从数据库读取
            $dbTtl = SystemConfig::getConfig('jwt_ttl');

            if ($dbTtl !== null) {
                // 使用数据库配置
                self::$config = [
                    'ttl' => (int)$dbTtl,
                    'refresh_ttl' => (int)SystemConfig::getConfig('jwt_refresh_ttl', self::DEFAULT_REFRESH_TTL),
                ];
            } else {
                // 降级到 .env 配置
                self::$config = [
                    'ttl' => (int)env('JWT_TTL', self::DEFAULT_TTL),
                    'refresh_ttl' => (int)env('JWT_REFRESH_TTL', self::DEFAULT_REFRESH_TTL),
                ];
            }
        } catch (\Exception $e) {
            // 数据库未就绪时使用 .env 配置
            self::$config = [
                'ttl' => (int)env('JWT_TTL', self::DEFAULT_TTL),
                'refresh_ttl' => (int)env('JWT_REFRESH_TTL', self::DEFAULT_REFRESH_TTL),
            ];
        }
    }

    /**
     * 清除缓存（用于配置更新后刷新）
     */
    public static function clearCache(): void
    {
        self::$config = null;
    }

    /**
     * 获取所有 JWT 配置
     * @return array
     */
    public static function getAll(): array
    {
        self::loadConfig();
        return self::$config;
    }
}
