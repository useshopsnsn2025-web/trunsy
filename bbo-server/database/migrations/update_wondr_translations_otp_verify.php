<?php

/**
 * 更新 Wondr 翻译 - 添加 OTP 验证页面翻译
 * 执行方式：php update_wondr_translations_otp_verify.php
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
            'key' => 'otpVerifyTitle',
            'locale' => 'zh-tw',
            'value' => '檢查你的簡訊',
        ],
        [
            'namespace' => 'wondr',
            'key' => 'otpVerifySubtitle',
            'locale' => 'zh-tw',
            'value' => '輸入發送到 {phone} 的6位數OTP驗證碼。',
        ],
        [
            'namespace' => 'wondr',
            'key' => 'resendCodeIn',
            'locale' => 'zh-tw',
            'value' => '重新發送驗證碼',
        ],

        // 英文
        [
            'namespace' => 'wondr',
            'key' => 'otpVerifyTitle',
            'locale' => 'en-us',
            'value' => 'Check your SMS',
        ],
        [
            'namespace' => 'wondr',
            'key' => 'otpVerifySubtitle',
            'locale' => 'en-us',
            'value' => 'Enter the 6-digit OTP code that was sent to {phone}.',
        ],
        [
            'namespace' => 'wondr',
            'key' => 'resendCodeIn',
            'locale' => 'en-us',
            'value' => 'Resend code in',
        ],

        // 日文
        [
            'namespace' => 'wondr',
            'key' => 'otpVerifyTitle',
            'locale' => 'ja-jp',
            'value' => 'SMSを確認',
        ],
        [
            'namespace' => 'wondr',
            'key' => 'otpVerifySubtitle',
            'locale' => 'ja-jp',
            'value' => '{phone}に送信された6桁のOTP認証コードを入力してください。',
        ],
        [
            'namespace' => 'wondr',
            'key' => 'resendCodeIn',
            'locale' => 'ja-jp',
            'value' => '再送信まで',
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
