<?php
/**
 * Cloudflare R2 配置
 *
 * 配置优先级：
 * 1. 数据库 system_configs 表（推荐，可在后台管理界面修改）
 * 2. .env 文件（作为降级方案，当数据库配置不存在时使用）
 *
 * 推荐使用方式：
 * 1. 执行 database/r2_storage_config.sql 创建数据库配置项
 * 2. 在后台管理 -> 系统设置 -> 存储设置 中配置 R2 参数
 *
 * 获取 R2 配置值的步骤：
 * 1. 登录 Cloudflare Dashboard (https://dash.cloudflare.com)
 * 2. 进入 R2 对象存储
 * 3. 创建存储桶（Bucket）
 * 4. 在 R2 概览页面点击 "管理 R2 API 令牌"
 * 5. 创建 API 令牌，获取 Access Key ID 和 Secret Access Key
 * 6. Account ID 在 Cloudflare 仪表盘右侧可以找到
 */

return [
    // 是否启用 R2（false 则使用本地存储）
    // 数据库配置键：r2_enabled
    'enabled' => env('R2_ENABLED', false),

    // Cloudflare Account ID
    // 数据库配置键：r2_account_id
    'account_id' => env('R2_ACCOUNT_ID', ''),

    // R2 Access Key ID
    // 数据库配置键：r2_access_key_id
    'access_key_id' => env('R2_ACCESS_KEY_ID', ''),

    // R2 Secret Access Key
    // 数据库配置键：r2_secret_access_key
    'secret_access_key' => env('R2_SECRET_ACCESS_KEY', ''),

    // 存储桶名称
    // 数据库配置键：r2_bucket
    'bucket' => env('R2_BUCKET', 'bbo-assets'),

    // R2 端点 URL（格式：https://<account_id>.r2.cloudflarestorage.com）
    // 数据库配置键：r2_endpoint
    'endpoint' => env('R2_ENDPOINT', ''),

    // 公开访问域名（需要在 R2 设置中配置自定义域名或使用 r2.dev 域名）
    // 例如：https://assets.yourdomain.com 或 https://pub-xxx.r2.dev
    // 数据库配置键：r2_public_url
    'public_url' => env('R2_PUBLIC_URL', ''),

    // 区域（R2 使用 auto）
    'region' => 'auto',

    // 上传配置（仅从文件配置读取，不支持数据库配置）
    'upload' => [
        // 最大图片大小 (5MB)
        'max_image_size' => 5 * 1024 * 1024,
        // 最大视频大小 (50MB)
        'max_video_size' => 50 * 1024 * 1024,
        // 允许的图片类型
        'allowed_image_types' => ['image/jpeg', 'image/png', 'image/gif', 'image/webp'],
        // 允许的视频类型
        'allowed_video_types' => ['video/mp4', 'video/quicktime', 'video/x-msvideo'],
    ],
];
