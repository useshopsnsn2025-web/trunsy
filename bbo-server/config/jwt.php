<?php
/**
 * JWT 配置
 *
 * 配置优先级：
 * 1. 数据库 system_configs 表（推荐，可在后台管理界面修改）
 *    - jwt_ttl: Token 有效期（秒）
 *    - jwt_refresh_ttl: 刷新 Token 有效期（秒）
 * 2. .env 文件（作为降级方案，当数据库配置不存在时使用）
 *
 * 推荐使用方式：
 * 1. 执行 database/jwt_security_config.sql 创建数据库配置项
 * 2. 在后台管理 -> 系统设置 -> 安全设置 中配置 JWT 参数
 *
 * 注意：TTL 配置由 app\common\service\JwtConfig 在运行时从数据库读取
 * 下面的 ttl 和 refresh_ttl 值仅作为 .env 不存在时的默认值
 */

return [
    'secret'      => env('JWT_SECRET'),
    //Asymmetric key
    'public_key'  => env('JWT_PUBLIC_KEY'),
    'private_key' => env('JWT_PRIVATE_KEY'),
    'password'    => env('JWT_PASSWORD'),

    // JWT time to live (秒)
    // 数据库配置键：jwt_ttl（在后台 系统设置 -> 安全设置 中配置）
    // 默认值：7200 秒 = 2 小时
    'ttl'         => env('JWT_TTL', 7200),

    // Refresh time to live (秒)
    // 数据库配置键：jwt_refresh_ttl（在后台 系统设置 -> 安全设置 中配置）
    // 默认值：604800 秒 = 7 天
    'refresh_ttl' => env('JWT_REFRESH_TTL', 604800),

    //JWT hashing algorithm
    'algo'        => env('JWT_ALGO', 'HS256'),
    //token获取方式，数组靠前值优先
    'token_mode'    => ['header', 'cookie', 'param'],
    //黑名单后有效期
    'blacklist_grace_period' => env('BLACKLIST_GRACE_PERIOD', 10),
    'blacklist_storage' => thans\jwt\provider\storage\Tp5::class,
];
