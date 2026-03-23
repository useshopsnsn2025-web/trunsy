<?php
/**
 * 执行 Wondr 钱包翻译迁移
 * 使用方法:
 * 1. 在浏览器访问此文件（需要将文件放到 public 目录下）
 * 2. 或使用命令行: php run_wondr_translations.php
 * 3. 执行后需要清理缓存：删除 runtime/cache/ 目录
 */

// 数据库配置
$host = '127.0.0.1';
$port = 3306;
$dbname = 'bbo';
$username = 'bbo';
$password = '123456';

try {
    $pdo = new PDO(
        "mysql:host={$host};port={$port};dbname={$dbname};charset=utf8mb4",
        $username,
        $password,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );

    echo "已连接到数据库\n\n";

    // Wondr 翻译数据
    $translations = [
        // 繁体中文 (zh-tw)
        'zh-tw' => [
            'pageTitle' => 'Wondr 錢包',
            'backButton' => '返回',
            'promoCodeLink' => '有推薦碼/優惠碼嗎？',
            'mainTitle' => '你想怎麼稱呼？',
            'subtitle' => '讓我們更了解你！',
            'nicknamePlaceholder' => '輸入你的暱稱',
            'charCounter' => '{current}/{max}',
            'continueButton' => '繼續',
            'pleaseEnterNickname' => '請輸入暱稱',
            'promoCodeFeature' => '推薦碼功能',
        ],

        // 英文 (en-us)
        'en-us' => [
            'pageTitle' => 'Wondr Wallet',
            'backButton' => 'Back',
            'promoCodeLink' => 'Got a referral/promo code?',
            'mainTitle' => 'How do you like to be called?',
            'subtitle' => 'Let\'s get to know you better!',
            'nicknamePlaceholder' => 'Enter your nickname',
            'charCounter' => '{current}/{max}',
            'continueButton' => 'Continue',
            'pleaseEnterNickname' => 'Please enter your nickname',
            'promoCodeFeature' => 'Promo code feature',
        ],

        // 日文 (ja-jp)
        'ja-jp' => [
            'pageTitle' => 'Wondr ウォレット',
            'backButton' => '戻る',
            'promoCodeLink' => '紹介コード/プロモコードをお持ちですか？',
            'mainTitle' => 'どのようにお呼びすればよろしいですか？',
            'subtitle' => 'もっとあなたのことを知りたいです！',
            'nicknamePlaceholder' => 'ニックネームを入力',
            'charCounter' => '{current}/{max}',
            'continueButton' => '続ける',
            'pleaseEnterNickname' => 'ニックネームを入力してください',
            'promoCodeFeature' => 'プロモコード機能',
        ],
    ];

    $namespace = 'wondr';
    $now = date('Y-m-d H:i:s');
    $totalInserted = 0;
    $totalUpdated = 0;

    $pdo->beginTransaction();

    foreach ($translations as $locale => $items) {
        foreach ($items as $key => $value) {
            // 检查是否已存在
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM ui_translations WHERE locale = ? AND namespace = ? AND `key` = ?");
            $stmt->execute([$locale, $namespace, $key]);
            $exists = $stmt->fetchColumn() > 0;

            if ($exists) {
                // 更新
                $stmt = $pdo->prepare("UPDATE ui_translations SET `value` = ?, updated_at = ? WHERE locale = ? AND namespace = ? AND `key` = ?");
                $stmt->execute([$value, $now, $locale, $namespace, $key]);
                $totalUpdated++;
            } else {
                // 插入
                $stmt = $pdo->prepare("INSERT INTO ui_translations (locale, namespace, `key`, `value`, is_original, is_auto_translated, created_at, updated_at) VALUES (?, ?, ?, ?, 1, 0, ?, ?)");
                $stmt->execute([$locale, $namespace, $key, $value, $now, $now]);
                $totalInserted++;
            }
        }
    }

    $pdo->commit();

    echo "✅ 迁移执行成功！\n";
    echo "插入了 {$totalInserted} 条新翻译记录\n";
    echo "更新了 {$totalUpdated} 条已有翻译记录\n";
    echo "\n⚠️  重要：请删除 runtime/cache/ 目录下的缓存文件！\n";

} catch (PDOException $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo "❌ 错误: " . $e->getMessage() . "\n";
    exit(1);
}
