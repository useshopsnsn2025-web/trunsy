<?php
/**
 * 邮件配置
 */
return [
    // SMTP 配置
    'driver' => env('MAIL_DRIVER', 'smtp'),
    'host' => env('MAIL_HOST', 'smtp.gmail.com'),
    'port' => (int)env('MAIL_PORT', 587),
    'username' => env('MAIL_USERNAME', ''),
    'password' => env('MAIL_PASSWORD', ''),
    'encryption' => env('MAIL_ENCRYPTION', 'tls'), // tls or ssl

    // 发件人信息
    'from' => [
        'address' => env('MAIL_FROM_ADDRESS', 'turnsyshop@gmail.com'),
        'name' => env('MAIL_FROM_NAME', 'TURNSY Marketplace'),
    ],

    // 邮件模板目录
    'template_path' => app_path() . 'view/email/',

    // 是否启用邮件发送（测试环境可设为 false）
    'enabled' => env('MAIL_ENABLED', true),

    // 是否记录邮件日志
    'log_enabled' => true,

    // 发送失败重试次数
    'retry_times' => 3,

    // 发送超时时间（秒）
    'timeout' => 30,

    // Debug 模式（记录详细日志）
    'debug' => env('MAIL_DEBUG', false),
];
