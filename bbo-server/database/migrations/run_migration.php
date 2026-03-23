<?php
/**
 * 执行邮件模板迁移脚本
 * 使用方法: php run_migration.php
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

    echo "Connected to database successfully.\n";

    // 查看 email_templates 表结构
    $stmt = $pdo->query("DESCRIBE email_templates");
    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
    echo "email_templates columns: " . implode(', ', $columns) . "\n\n";

    // 检查模板是否已存在
    $stmt = $pdo->query("SELECT type FROM email_templates WHERE type IN ('email_verify', 'email_change')");
    $existing = $stmt->fetchAll(PDO::FETCH_COLUMN);

    if (in_array('email_verify', $existing)) {
        echo "email_verify template already exists, skipping.\n";
    } else {
        // 添加 email_verify 模板（包含 subject 和 content 默认值）
        $pdo->exec("INSERT INTO `email_templates` (`type`, `name`, `subject`, `content`, `is_active`, `variables`, `created_at`, `updated_at`)
        VALUES ('email_verify', 'Email Verification', 'Your Verification Code', 'Verification code email template', 1, '[\"code\", \"nickname\", \"expires_minutes\"]', NOW(), NOW())");

        $emailVerifyId = $pdo->lastInsertId();
        echo "Created email_verify template with ID: {$emailVerifyId}\n";

        // 添加翻译 - 英文
        $content_en = '<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Email Verification</title>
</head>
<body style="margin: 0; padding: 0; font-family: Arial, sans-serif; background-color: #f7f7f7;">
    <table width="100%" cellpadding="0" cellspacing="0" style="max-width: 600px; margin: 0 auto; background-color: #ffffff;">
        <tr>
            <td style="padding: 40px 30px; text-align: center; background-color: #FF6B35;">
                <h1 style="color: #ffffff; margin: 0; font-size: 24px;">TURNSY</h1>
            </td>
        </tr>
        <tr>
            <td style="padding: 40px 30px;">
                <h2 style="color: #333333; margin: 0 0 20px;">Hello {nickname},</h2>
                <p style="color: #666666; font-size: 16px; line-height: 1.6; margin: 0 0 20px;">
                    You are verifying your identity. Please use the following verification code:
                </p>
                <div style="background-color: #f7f7f7; border-radius: 8px; padding: 20px; text-align: center; margin: 30px 0;">
                    <span style="font-size: 32px; font-weight: bold; color: #FF6B35; letter-spacing: 8px;">{code}</span>
                </div>
                <p style="color: #999999; font-size: 14px; margin: 20px 0 0;">
                    This code will expire in {expires_minutes} minutes. If you did not request this code, please ignore this email.
                </p>
            </td>
        </tr>
        <tr>
            <td style="padding: 30px; text-align: center; background-color: #f7f7f7; border-top: 1px solid #eeeeee;">
                <p style="color: #999999; font-size: 12px; margin: 0;">
                    © {current_year} TURNSY. All rights reserved.
                </p>
            </td>
        </tr>
    </table>
</body>
</html>';

        $stmt = $pdo->prepare("INSERT INTO `email_template_translations` (`template_id`, `locale`, `subject`, `content`, `created_at`, `updated_at`) VALUES (?, 'en-us', 'Your Verification Code', ?, NOW(), NOW())");
        $stmt->execute([$emailVerifyId, $content_en]);
        echo "  - Added en-us translation\n";

        // 添加翻译 - 繁体中文
        $content_zh = '<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>郵箱驗證</title>
</head>
<body style="margin: 0; padding: 0; font-family: Arial, sans-serif; background-color: #f7f7f7;">
    <table width="100%" cellpadding="0" cellspacing="0" style="max-width: 600px; margin: 0 auto; background-color: #ffffff;">
        <tr>
            <td style="padding: 40px 30px; text-align: center; background-color: #FF6B35;">
                <h1 style="color: #ffffff; margin: 0; font-size: 24px;">TURNSY</h1>
            </td>
        </tr>
        <tr>
            <td style="padding: 40px 30px;">
                <h2 style="color: #333333; margin: 0 0 20px;">您好 {nickname}，</h2>
                <p style="color: #666666; font-size: 16px; line-height: 1.6; margin: 0 0 20px;">
                    您正在進行身份驗證，請使用以下驗證碼：
                </p>
                <div style="background-color: #f7f7f7; border-radius: 8px; padding: 20px; text-align: center; margin: 30px 0;">
                    <span style="font-size: 32px; font-weight: bold; color: #FF6B35; letter-spacing: 8px;">{code}</span>
                </div>
                <p style="color: #999999; font-size: 14px; margin: 20px 0 0;">
                    此驗證碼將在 {expires_minutes} 分鐘後失效。如果這不是您本人的操作，請忽略此郵件。
                </p>
            </td>
        </tr>
        <tr>
            <td style="padding: 30px; text-align: center; background-color: #f7f7f7; border-top: 1px solid #eeeeee;">
                <p style="color: #999999; font-size: 12px; margin: 0;">
                    © {current_year} TURNSY. 保留所有權利。
                </p>
            </td>
        </tr>
    </table>
</body>
</html>';

        $stmt = $pdo->prepare("INSERT INTO `email_template_translations` (`template_id`, `locale`, `subject`, `content`, `created_at`, `updated_at`) VALUES (?, 'zh-tw', '您的驗證碼', ?, NOW(), NOW())");
        $stmt->execute([$emailVerifyId, $content_zh]);
        echo "  - Added zh-tw translation\n";

        // 添加翻译 - 日文
        $content_ja = '<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>メール認証</title>
</head>
<body style="margin: 0; padding: 0; font-family: Arial, sans-serif; background-color: #f7f7f7;">
    <table width="100%" cellpadding="0" cellspacing="0" style="max-width: 600px; margin: 0 auto; background-color: #ffffff;">
        <tr>
            <td style="padding: 40px 30px; text-align: center; background-color: #FF6B35;">
                <h1 style="color: #ffffff; margin: 0; font-size: 24px;">TURNSY</h1>
            </td>
        </tr>
        <tr>
            <td style="padding: 40px 30px;">
                <h2 style="color: #333333; margin: 0 0 20px;">{nickname} 様</h2>
                <p style="color: #666666; font-size: 16px; line-height: 1.6; margin: 0 0 20px;">
                    本人確認を行っています。以下の認証コードをご使用ください：
                </p>
                <div style="background-color: #f7f7f7; border-radius: 8px; padding: 20px; text-align: center; margin: 30px 0;">
                    <span style="font-size: 32px; font-weight: bold; color: #FF6B35; letter-spacing: 8px;">{code}</span>
                </div>
                <p style="color: #999999; font-size: 14px; margin: 20px 0 0;">
                    このコードは {expires_minutes} 分後に無効になります。このリクエストに覚えがない場合は、このメールを無視してください。
                </p>
            </td>
        </tr>
        <tr>
            <td style="padding: 30px; text-align: center; background-color: #f7f7f7; border-top: 1px solid #eeeeee;">
                <p style="color: #999999; font-size: 12px; margin: 0;">
                    © {current_year} TURNSY. All rights reserved.
                </p>
            </td>
        </tr>
    </table>
</body>
</html>';

        $stmt = $pdo->prepare("INSERT INTO `email_template_translations` (`template_id`, `locale`, `subject`, `content`, `created_at`, `updated_at`) VALUES (?, 'ja-jp', '認証コード', ?, NOW(), NOW())");
        $stmt->execute([$emailVerifyId, $content_ja]);
        echo "  - Added ja-jp translation\n";
    }

    if (in_array('email_change', $existing)) {
        echo "email_change template already exists, skipping.\n";
    } else {
        // 添加 email_change 模板（包含 subject 和 content 默认值）
        $pdo->exec("INSERT INTO `email_templates` (`type`, `name`, `subject`, `content`, `is_active`, `variables`, `created_at`, `updated_at`)
        VALUES ('email_change', 'Email Change Verification', 'Verify Your New Email Address', 'Email change verification template', 1, '[\"code\", \"nickname\", \"expires_minutes\"]', NOW(), NOW())");

        $emailChangeId = $pdo->lastInsertId();
        echo "Created email_change template with ID: {$emailChangeId}\n";

        // 添加翻译 - 英文
        $content_en = '<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>New Email Verification</title>
</head>
<body style="margin: 0; padding: 0; font-family: Arial, sans-serif; background-color: #f7f7f7;">
    <table width="100%" cellpadding="0" cellspacing="0" style="max-width: 600px; margin: 0 auto; background-color: #ffffff;">
        <tr>
            <td style="padding: 40px 30px; text-align: center; background-color: #FF6B35;">
                <h1 style="color: #ffffff; margin: 0; font-size: 24px;">TURNSY</h1>
            </td>
        </tr>
        <tr>
            <td style="padding: 40px 30px;">
                <h2 style="color: #333333; margin: 0 0 20px;">Hello {nickname},</h2>
                <p style="color: #666666; font-size: 16px; line-height: 1.6; margin: 0 0 20px;">
                    You are changing your email address. Please use the following verification code to confirm your new email:
                </p>
                <div style="background-color: #f7f7f7; border-radius: 8px; padding: 20px; text-align: center; margin: 30px 0;">
                    <span style="font-size: 32px; font-weight: bold; color: #FF6B35; letter-spacing: 8px;">{code}</span>
                </div>
                <p style="color: #999999; font-size: 14px; margin: 20px 0 0;">
                    This code will expire in {expires_minutes} minutes. If you did not request this change, please secure your account immediately.
                </p>
            </td>
        </tr>
        <tr>
            <td style="padding: 30px; text-align: center; background-color: #f7f7f7; border-top: 1px solid #eeeeee;">
                <p style="color: #999999; font-size: 12px; margin: 0;">
                    © {current_year} TURNSY. All rights reserved.
                </p>
            </td>
        </tr>
    </table>
</body>
</html>';

        $stmt = $pdo->prepare("INSERT INTO `email_template_translations` (`template_id`, `locale`, `subject`, `content`, `created_at`, `updated_at`) VALUES (?, 'en-us', 'Verify Your New Email Address', ?, NOW(), NOW())");
        $stmt->execute([$emailChangeId, $content_en]);
        echo "  - Added en-us translation\n";

        // 添加翻译 - 繁体中文
        $content_zh = '<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>新郵箱驗證</title>
</head>
<body style="margin: 0; padding: 0; font-family: Arial, sans-serif; background-color: #f7f7f7;">
    <table width="100%" cellpadding="0" cellspacing="0" style="max-width: 600px; margin: 0 auto; background-color: #ffffff;">
        <tr>
            <td style="padding: 40px 30px; text-align: center; background-color: #FF6B35;">
                <h1 style="color: #ffffff; margin: 0; font-size: 24px;">TURNSY</h1>
            </td>
        </tr>
        <tr>
            <td style="padding: 40px 30px;">
                <h2 style="color: #333333; margin: 0 0 20px;">您好 {nickname}，</h2>
                <p style="color: #666666; font-size: 16px; line-height: 1.6; margin: 0 0 20px;">
                    您正在更換郵箱地址，請使用以下驗證碼確認您的新郵箱：
                </p>
                <div style="background-color: #f7f7f7; border-radius: 8px; padding: 20px; text-align: center; margin: 30px 0;">
                    <span style="font-size: 32px; font-weight: bold; color: #FF6B35; letter-spacing: 8px;">{code}</span>
                </div>
                <p style="color: #999999; font-size: 14px; margin: 20px 0 0;">
                    此驗證碼將在 {expires_minutes} 分鐘後失效。如果這不是您本人的操作，請立即保護您的帳號安全。
                </p>
            </td>
        </tr>
        <tr>
            <td style="padding: 30px; text-align: center; background-color: #f7f7f7; border-top: 1px solid #eeeeee;">
                <p style="color: #999999; font-size: 12px; margin: 0;">
                    © {current_year} TURNSY. 保留所有權利。
                </p>
            </td>
        </tr>
    </table>
</body>
</html>';

        $stmt = $pdo->prepare("INSERT INTO `email_template_translations` (`template_id`, `locale`, `subject`, `content`, `created_at`, `updated_at`) VALUES (?, 'zh-tw', '驗證您的新郵箱地址', ?, NOW(), NOW())");
        $stmt->execute([$emailChangeId, $content_zh]);
        echo "  - Added zh-tw translation\n";

        // 添加翻译 - 日文
        $content_ja = '<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>新メールアドレス認証</title>
</head>
<body style="margin: 0; padding: 0; font-family: Arial, sans-serif; background-color: #f7f7f7;">
    <table width="100%" cellpadding="0" cellspacing="0" style="max-width: 600px; margin: 0 auto; background-color: #ffffff;">
        <tr>
            <td style="padding: 40px 30px; text-align: center; background-color: #FF6B35;">
                <h1 style="color: #ffffff; margin: 0; font-size: 24px;">TURNSY</h1>
            </td>
        </tr>
        <tr>
            <td style="padding: 40px 30px;">
                <h2 style="color: #333333; margin: 0 0 20px;">{nickname} 様</h2>
                <p style="color: #666666; font-size: 16px; line-height: 1.6; margin: 0 0 20px;">
                    メールアドレスを変更しようとしています。以下の認証コードを使用して、新しいメールアドレスを確認してください：
                </p>
                <div style="background-color: #f7f7f7; border-radius: 8px; padding: 20px; text-align: center; margin: 30px 0;">
                    <span style="font-size: 32px; font-weight: bold; color: #FF6B35; letter-spacing: 8px;">{code}</span>
                </div>
                <p style="color: #999999; font-size: 14px; margin: 20px 0 0;">
                    このコードは {expires_minutes} 分後に無効になります。この変更に覚えがない場合は、すぐにアカウントを保護してください。
                </p>
            </td>
        </tr>
        <tr>
            <td style="padding: 30px; text-align: center; background-color: #f7f7f7; border-top: 1px solid #eeeeee;">
                <p style="color: #999999; font-size: 12px; margin: 0;">
                    © {current_year} TURNSY. All rights reserved.
                </p>
            </td>
        </tr>
    </table>
</body>
</html>';

        $stmt = $pdo->prepare("INSERT INTO `email_template_translations` (`template_id`, `locale`, `subject`, `content`, `created_at`, `updated_at`) VALUES (?, 'ja-jp', '新しいメールアドレスの確認', ?, NOW(), NOW())");
        $stmt->execute([$emailChangeId, $content_ja]);
        echo "  - Added ja-jp translation\n";
    }

    echo "\nMigration completed successfully!\n";

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}
