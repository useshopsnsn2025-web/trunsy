<?php
/**
 * OAuth 第三方登录配置
 */
return [
    // Google OAuth 配置
    'google' => [
        'client_id' => env('GOOGLE.CLIENT_ID', ''),
        'client_secret' => env('GOOGLE.CLIENT_SECRET', ''),
    ],

    // Apple OAuth 配置 (预留)
    'apple' => [
        'client_id' => env('APPLE.CLIENT_ID', ''),
        'team_id' => env('APPLE.TEAM_ID', ''),
        'key_id' => env('APPLE.KEY_ID', ''),
        'private_key' => env('APPLE.PRIVATE_KEY', ''),
    ],
];
