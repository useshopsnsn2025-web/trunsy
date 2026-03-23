<?php

/**
 * 更新 Wondr 翻译 - 添加 OTP 安全提示信息
 * 执行方式：php think run update_wondr_translations_otp_security.php
 */

require __DIR__ . '/../../vendor/autoload.php';

$app = new think\App();
$app->initialize();

use think\facade\Db;

try {
    // 新增的翻译键值对
    $translations = [
        // 繁体中文
        [
            'namespace' => 'wondr',
            'key' => 'otpSecurityTitle',
            'locale' => 'zh-tw',
            'value' => '加強OTP安全的步驟',
        ],
        [
            'namespace' => 'wondr',
            'key' => 'otpSecurityContent',
            'locale' => 'zh-tw',
            'value' => "1. 你可以選擇通過SMS或WhatsApp接收OTP。我們建議你選擇SMS，因為它更安全。\n\n2. 如果你更喜歡使用WhatsApp，請啟用PIN（設定>帳號>雙重驗證）以提高WhatsApp驗證的安全性。\n\n3. 永遠不要與任何人分享OTP！",
        ],

        // 英文
        [
            'namespace' => 'wondr',
            'key' => 'otpSecurityTitle',
            'locale' => 'en-us',
            'value' => 'Steps to enhance OTP security',
        ],
        [
            'namespace' => 'wondr',
            'key' => 'otpSecurityContent',
            'locale' => 'en-us',
            'value' => "1. You can choose to receive OTP via SMS or WhatsApp. We recommend you to choose SMS because it's safer.\n\n2. If you prefer using WhatsApp, make your WhatsApp verification more secure by enabling PIN (Settings > Account > Two-Step Verification).\n\n3. Don't ever share OTP with anyone!",
        ],

        // 日文
        [
            'namespace' => 'wondr',
            'key' => 'otpSecurityTitle',
            'locale' => 'ja-jp',
            'value' => 'OTPセキュリティを強化する手順',
        ],
        [
            'namespace' => 'wondr',
            'key' => 'otpSecurityContent',
            'locale' => 'ja-jp',
            'value' => "1. SMSまたはWhatsAppでOTPを受信することができます。より安全なSMSを選択することをお勧めします。\n\n2. WhatsAppを使用する場合は、PIN（設定>アカウント>2段階認証）を有効にして、WhatsApp認証のセキュリティを強化してください。\n\n3. OTPを他人と共有しないでください！",
        ],
    ];

    $inserted = 0;
    $updated = 0;

    foreach ($translations as $translation) {
        $exists = Db::name('ui_translations')
            ->where('namespace', $translation['namespace'])
            ->where('key', $translation['key'])
            ->where('locale', $translation['locale'])
            ->find();

        if ($exists) {
            Db::name('ui_translations')
                ->where('id', $exists['id'])
                ->update([
                    'value' => $translation['value'],
                    'updated_at' => date('Y-m-d H:i:s'),
                ]);
            $updated++;
            echo "Updated: {$translation['namespace']}.{$translation['key']} ({$translation['locale']})\n";
        } else {
            Db::name('ui_translations')->insert([
                'namespace' => $translation['namespace'],
                'key' => $translation['key'],
                'locale' => $translation['locale'],
                'value' => $translation['value'],
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ]);
            $inserted++;
            echo "Inserted: {$translation['namespace']}.{$translation['key']} ({$translation['locale']})\n";
        }
    }

    echo "\n执行完成！\n";
    echo "新增：{$inserted} 条\n";
    echo "更新：{$updated} 条\n";
    echo "\n请清理缓存：\n";
    echo "rm -rf runtime/cache/*\n";

} catch (\Exception $e) {
    echo "错误：" . $e->getMessage() . "\n";
    echo "堆栈跟踪：\n" . $e->getTraceAsString() . "\n";
}
