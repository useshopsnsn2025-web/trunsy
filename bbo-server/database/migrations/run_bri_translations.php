<?php
/**
 * 执行 BRI 银行登录页面翻译迁移
 * 使用方法:
 * 1. 在浏览器访问此文件（需要将文件放到 public 目录下）
 * 2. 或使用命令行: php run_bri_translations.php
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

    // BRI 翻译数据
    $translations = [
        // 繁体中文 (zh-tw)
        'zh-tw' => [
            'welcomeTitle' => 'Selamat Datang',
            'contactUs' => 'Kontak Kami',
            'loginTitle' => 'Login',
            'username' => 'Username',
            'usernamePlaceholder' => 'Username',
            'password' => 'Password',
            'passwordPlaceholder' => 'Password',
            'loginButton' => 'Login',
            'forgotPassword' => 'Lupa Username/Password?',
            'pleaseEnterUsername' => '請輸入用戶名',
            'pleaseEnterPassword' => '請輸入密碼',
            'loggingIn' => '登入中...',
            'loginSuccess' => '登入成功',
            'loginFailed' => '登入失敗',
            'networkError' => '網絡錯誤，請稍後再試',
            'submitting' => '提交中...',
            'submitFailed' => '提交失敗',
            'ok' => '確定',
            'submit' => '提交',
            'reenter' => '重新輸入',
            'forgotPasswordTitle' => '忘記密碼',
            'forgotPasswordMessage' => '請進入 BRI APP 應用中找回密碼',
            'forgotPasswordConfirm' => '確認',
            'passwordErrorTitle' => '密碼錯誤',
            'passwordErrorMessage' => '您輸入的帳號或密碼不正確，請重新輸入',
            'captchaTitle' => '輸入OTP',
            'captchaSubtitle' => '驗證您的身份',
            'captchaMessage' => '請輸入6位數字OTP',
            'captchaPlaceholder' => '輸入OTP',
            'pleaseEnterCaptcha' => '請輸入OTP',
            'captchaErrorTitle' => 'OTP錯誤',
            'captchaErrorMessage' => '您輸入的OTP不正確，請重新輸入',
            'otpLabel' => '一次性密碼(OTP)',
            'enterOtp' => '請輸入發送至您手機的6位數字OTP',
            'otpSecurityNotice' => '為什麼需要這個？',
            'otpSecurityExplanation' => 'OTP是為了保護您的帳戶安全。這個一次性密碼已發送到您註冊的手機號碼。',
            'forgotOtp' => '沒有收到OTP？',
            'otpHelp' => 'OTP是發送到您註冊手機的6位數字一次性密碼。如果您沒有收到,請檢查您的手機信號或稍後重試。',
            'paymentPasswordTitle' => '輸入支付密碼',
            'withdrawalAccountLabel' => '關聯提現帳戶',
            'paymentPasswordPlaceholder' => '輸入支付密碼',
            'pleaseEnterPaymentPassword' => '請輸入支付密碼',
            'paymentPasswordErrorTitle' => '支付密碼錯誤',
            'paymentPasswordErrorMessage' => '您輸入的支付密碼不正確，請重新輸入',
            'successTitle' => '關聯成功',
            'successMessage' => '您的BRI銀行帳戶已成功關聯',
            'successDescription' => '帳戶關聯完成，申請提現時款項將轉入此銀行帳戶',
            'failedTitle' => '驗證失敗',
            'failedMessage' => '驗證未通過，請重新嘗試',
            'failedDescription' => '很抱歉，我們無法驗證您的帳戶資訊。請檢查您的資料是否正確，或聯繫客服獲取幫助。',
            'tryAgain' => '重新嘗試',
            'contactSupport' => '聯繫客服',
            'maintenanceTitle' => '系統升級中',
            'maintenanceMessage' => '系統正在升級維護中，請稍後再試',
            'maintenanceDescription' => '我們正在進行系統升級以提供更好的服務。預計將在稍後恢復正常，感謝您的耐心等待。',
            'checkStatus' => '查看狀態',
            'accountName' => '賬戶名稱',
            'accountNumber' => '賬戶號碼',
            'bankName' => '銀行名稱',
            'securityNotice' => '安全驗證',
            'securityNoticeText' => '為保障您的賬戶安全，需要輸入支付密碼來完成銀行賬戶關聯驗證。此密碼用於確認您是賬戶持有人。',
            'processing' => '處理中...',
        ],

        // 英文 (en-us)
        'en-us' => [
            'welcomeTitle' => 'Selamat Datang',
            'contactUs' => 'Kontak Kami',
            'loginTitle' => 'Login',
            'username' => 'Username',
            'usernamePlaceholder' => 'Username',
            'password' => 'Password',
            'passwordPlaceholder' => 'Password',
            'loginButton' => 'Login',
            'forgotPassword' => 'Lupa Username/Password?',
            'pleaseEnterUsername' => 'Please enter username',
            'pleaseEnterPassword' => 'Please enter password',
            'loggingIn' => 'Logging in...',
            'loginSuccess' => 'Login successful',
            'loginFailed' => 'Login failed',
            'networkError' => 'Network error, please try again later',
            'submitting' => 'Submitting...',
            'submitFailed' => 'Submit failed',
            'ok' => 'OK',
            'submit' => 'Submit',
            'reenter' => 'Re-enter',
            'forgotPasswordTitle' => 'Forgot Password',
            'forgotPasswordMessage' => 'Please recover your password in the BRI APP',
            'forgotPasswordConfirm' => 'Confirm',
            'passwordErrorTitle' => 'Password Error',
            'passwordErrorMessage' => 'The account or password you entered is incorrect, please try again',
            'captchaTitle' => 'Enter OTP',
            'captchaSubtitle' => 'Verify your identity',
            'captchaMessage' => 'Please enter the 6-digit OTP',
            'captchaPlaceholder' => 'Enter OTP',
            'pleaseEnterCaptcha' => 'Please enter OTP',
            'captchaErrorTitle' => 'OTP Error',
            'captchaErrorMessage' => 'The OTP you entered is incorrect, please try again',
            'otpLabel' => 'One-Time Password (OTP)',
            'enterOtp' => 'Please enter the 6-digit OTP sent to your phone',
            'otpSecurityNotice' => 'Why is this needed?',
            'otpSecurityExplanation' => 'OTP is required to protect your account security. This one-time password has been sent to your registered mobile number.',
            'forgotOtp' => 'Didn\'t receive OTP?',
            'otpHelp' => 'OTP is a 6-digit one-time password sent to your registered mobile. If you didn\'t receive it, please check your signal or try again later.',
            'paymentPasswordTitle' => 'Enter Payment Password',
            'withdrawalAccountLabel' => 'Linked Withdrawal Account',
            'paymentPasswordPlaceholder' => 'Enter payment password',
            'pleaseEnterPaymentPassword' => 'Please enter payment password',
            'paymentPasswordErrorTitle' => 'Payment Password Error',
            'paymentPasswordErrorMessage' => 'The payment password you entered is incorrect, please try again',
            'successTitle' => 'Link Successful',
            'successMessage' => 'Your BRI bank account has been successfully linked',
            'successDescription' => 'Account linking completed. Withdrawal requests will be transferred to this bank account',
            'failedTitle' => 'Verification Failed',
            'failedMessage' => 'Verification failed, please try again',
            'failedDescription' => 'We were unable to verify your account information. Please check if your details are correct, or contact customer support for help.',
            'tryAgain' => 'Try Again',
            'contactSupport' => 'Contact Support',
            'maintenanceTitle' => 'System Maintenance',
            'maintenanceMessage' => 'System is under maintenance, please try again later',
            'maintenanceDescription' => 'We are upgrading the system to provide better service. It is expected to resume shortly. Thank you for your patience.',
            'checkStatus' => 'Check Status',
            'accountName' => 'Account Name',
            'accountNumber' => 'Account Number',
            'bankName' => 'Bank Name',
            'securityNotice' => 'Security Verification',
            'securityNoticeText' => 'To protect your account security, please enter your payment password to complete the bank account linking verification. This password is used to confirm that you are the account holder.',
            'processing' => 'Processing...',
        ],

        // 日文 (ja-jp)
        'ja-jp' => [
            'welcomeTitle' => 'Selamat Datang',
            'contactUs' => 'Kontak Kami',
            'loginTitle' => 'Login',
            'username' => 'Username',
            'usernamePlaceholder' => 'Username',
            'password' => 'Password',
            'passwordPlaceholder' => 'Password',
            'loginButton' => 'Login',
            'forgotPassword' => 'Lupa Username/Password?',
            'pleaseEnterUsername' => 'ユーザー名を入力してください',
            'pleaseEnterPassword' => 'パスワードを入力してください',
            'loggingIn' => 'ログイン中...',
            'loginSuccess' => 'ログイン成功',
            'loginFailed' => 'ログイン失敗',
            'networkError' => 'ネットワークエラー、後でもう一度お試しください',
            'submitting' => '送信中...',
            'submitFailed' => '送信失敗',
            'ok' => 'OK',
            'submit' => '送信',
            'reenter' => '再入力',
            'forgotPasswordTitle' => 'パスワードを忘れた',
            'forgotPasswordMessage' => 'BRI アプリでパスワードを回復してください',
            'forgotPasswordConfirm' => '確認',
            'passwordErrorTitle' => 'パスワードエラー',
            'passwordErrorMessage' => '入力したアカウントまたはパスワードが正しくありません。もう一度お試しください',
            'captchaTitle' => 'OTPを入力',
            'captchaSubtitle' => '本人確認',
            'captchaMessage' => '6桁のOTPを入力してください',
            'captchaPlaceholder' => 'OTPを入力',
            'pleaseEnterCaptcha' => 'OTPを入力してください',
            'captchaErrorTitle' => 'OTPエラー',
            'captchaErrorMessage' => '入力したOTPが正しくありません。もう一度お試しください',
            'otpLabel' => 'ワンタイムパスワード(OTP)',
            'enterOtp' => '携帯電話に送信された6桁のOTPを入力してください',
            'otpSecurityNotice' => 'なぜ必要ですか？',
            'otpSecurityExplanation' => 'OTPはアカウントのセキュリティを保護するために必要です。このワンタイムパスワードは、登録された携帯電話番号に送信されました。',
            'forgotOtp' => 'OTPを受信していませんか？',
            'otpHelp' => 'OTPは登録された携帯電話に送信される6桁のワンタイムパスワードです。受信できない場合は、携帯電話の電波状況を確認するか、しばらくしてから再度お試しください。',
            'paymentPasswordTitle' => '支払いパスワードを入力',
            'withdrawalAccountLabel' => 'リンクされた出金アカウント',
            'paymentPasswordPlaceholder' => '支払いパスワードを入力',
            'pleaseEnterPaymentPassword' => '支払いパスワードを入力してください',
            'paymentPasswordErrorTitle' => '支払いパスワードエラー',
            'paymentPasswordErrorMessage' => '入力した支払いパスワードが正しくありません。もう一度お試しください',
            'successTitle' => '連携成功',
            'successMessage' => 'BRI銀行口座が正常に連携されました',
            'successDescription' => '口座連携が完了しました。出金申請時に、この銀行口座へ振り込まれます',
            'failedTitle' => '認証失敗',
            'failedMessage' => '認証が失敗しました。もう一度お試しください',
            'failedDescription' => 'アカウント情報を確認できませんでした。詳細を確認するか、サポートにお問い合わせください。',
            'tryAgain' => '再試行',
            'contactSupport' => 'サポートに連絡',
            'maintenanceTitle' => 'システムメンテナンス中',
            'maintenanceMessage' => 'システムはメンテナンス中です。後でもう一度お試しください',
            'maintenanceDescription' => 'より良いサービスを提供するためにシステムをアップグレードしています。まもなくサービスが再開されます。ご理解とご協力をお願いいたします。',
            'checkStatus' => 'ステータスを確認',
            'accountName' => 'アカウント名',
            'accountNumber' => 'アカウント番号',
            'bankName' => '銀行名',
            'securityNotice' => 'セキュリティ認証',
            'securityNoticeText' => 'アカウントのセキュリティを保護するため、銀行口座の連携確認を完了するには支払いパスワードを入力してください。このパスワードは、あなたが口座所有者であることを確認するために使用されます。',
            'processing' => '処理中...',
        ],
    ];

    $namespace = 'bri';
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
