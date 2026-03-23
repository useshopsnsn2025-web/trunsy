<?php
declare(strict_types=1);

namespace app\api\controller;

use think\Response;
use think\facade\Db;
use think\facade\Cache;
use thans\jwt\facade\JWTAuth;
use app\common\model\User;
use app\common\model\SystemConfig;
use app\common\model\PasswordReset;
use app\common\service\EmailService;
use app\api\validate\UserValidate;

/**
 * 认证控制器
 */
class Auth extends Base
{
    /**
     * 用户注册
     * @return Response
     */
    public function register(): Response
    {
        $data = input('post.');

        // 验证参数
        try {
            $this->validate($data, UserValidate::class, 'register');
        } catch (\Exception $e) {
            return $this->error($e->getMessage());
        }

        // 检查邮箱或手机号是否已存在
        $exists = User::where(function ($query) use ($data) {
            if (!empty($data['email'])) {
                $query->whereOr('email', $data['email']);
            }
            if (!empty($data['phone'])) {
                $query->whereOr('phone', $data['phone']);
            }
        })->find();

        if ($exists) {
            return $this->error('Account already exists');
        }

        Db::startTrans();
        try {
            // 创建用户
            $user = new User();
            $user->uuid = $this->generateUuid();
            $user->email = $data['email'] ?? null;
            $user->phone = $data['phone'] ?? null;
            $user->plain_password = $data['password'];
            $user->password = $data['password'];
            $user->nickname = $data['nickname'];
            $user->language = $this->locale;
            $user->currency = 'USD';
            $user->status = 1;
            $user->register_source = 'app';
            $user->save();

            // 生成 Token
            $token = JWTAuth::builder(['uid' => $user->id]);

            Db::commit();

            // 发送欢迎通知（优惠券 + 游戏次数 + 邮件 + 站内信）
            $this->sendWelcomeNotification($user);

            return $this->success([
                'token' => $token,
                'refreshToken' => '',
                'user' => $user->toApiArray()
            ], 'Register successful');

        } catch (\Exception $e) {
            Db::rollback();
            return $this->error('Register failed: ' . $e->getMessage());
        }
    }

    /**
     * 用户登录
     * @return Response
     */
    public function login(): Response
    {
        $data = input('post.');

        // 验证参数
        try {
            $this->validate($data, UserValidate::class, 'login');
        } catch (\Exception $e) {
            return $this->error($e->getMessage());
        }

        // 获取登录标识（邮箱或手机号）
        $loginIdentifier = $data['email'] ?? $data['phone'] ?? '';

        // 检查是否被锁定
        $lockCheck = $this->checkLoginLock($loginIdentifier);
        if ($lockCheck !== true) {
            return $this->error($lockCheck['key'], 1, null, $lockCheck['params']);
        }

        // 查找用户
        $user = User::where(function ($query) use ($data) {
            if (!empty($data['email'])) {
                $query->where('email', $data['email']);
            } elseif (!empty($data['phone'])) {
                $query->where('phone', $data['phone']);
            }
        })->find();

        if (!$user) {
            // 记录失败次数
            $failInfo = $this->recordLoginFailure($loginIdentifier);
            if ($failInfo['locked']) {
                return $this->error($failInfo['key'], 1, null, $failInfo['params']);
            }
            return $this->error('Account not found');
        }

        // 验证密码
        if (!$user->verifyPassword($data['password'])) {
            // 记录失败次数
            $failInfo = $this->recordLoginFailure($loginIdentifier);
            if ($failInfo['locked']) {
                return $this->error($failInfo['key'], 1, null, $failInfo['params']);
            }
            return $this->error('Password incorrect', 1, null, $failInfo['params']);
        }

        // 检查状态
        if ($user->status !== 1) {
            return $this->error('Account disabled');
        }

        // 登录成功，清除失败记录
        $this->clearLoginFailure($loginIdentifier);

        // 更新登录信息
        $user->updateLoginInfo(request()->ip());

        // 生成 Token
        $token = JWTAuth::builder(['uid' => $user->id]);

        return $this->success([
            'token' => $token,
            'refreshToken' => '',
            'user' => $user->toApiArray()
        ], 'Login successful');
    }

    /**
     * 社交登录
     * @return Response
     */
    public function socialLogin(): Response
    {
        $data = input('post.');

        $platform = $data['platform'] ?? '';
        $accessToken = $data['accessToken'] ?? '';

        if (!in_array($platform, ['google', 'apple'])) {
            return $this->error('Unsupported platform');
        }

        if (empty($accessToken)) {
            return $this->error('Access token required');
        }

        try {
            if ($platform === 'google') {
                return $this->handleGoogleLogin($accessToken);
            } elseif ($platform === 'apple') {
                // Apple 登录待实现
                return $this->error('Apple login not implemented yet');
            }
        } catch (\Exception $e) {
            \think\facade\Log::error('Social login error: ' . $e->getMessage());
            return $this->error('Login failed');
        }

        return $this->error('Unsupported platform');
    }

    /**
     * 处理 Google 登录
     * @param string $accessToken Google ID Token 或 APP OAuth 数据
     * @return Response
     */
    private function handleGoogleLogin(string $accessToken): Response
    {
        $socialUser = null;

        // 检查是否是 APP 端的 OAuth 数据（JSON 格式）
        $appAuthData = json_decode($accessToken, true);
        if ($appAuthData && isset($appAuthData['type']) && $appAuthData['type'] === 'app_oauth') {
            // APP 端通过 plus.oauth 登录，直接使用 openid 作为 google_id
            \think\facade\Log::info('Google login via APP OAuth: ' . json_encode($appAuthData));

            $socialUser = [
                'google_id' => $appAuthData['openid'] ?? '',
                'email' => $appAuthData['userInfo']['email'] ?? '',
                'name' => $appAuthData['userInfo']['nickname'] ?? $appAuthData['userInfo']['name'] ?? '',
                'picture' => $appAuthData['userInfo']['headimgurl'] ?? $appAuthData['userInfo']['avatar'] ?? '',
            ];
        } else {
            // H5 端使用 ID Token 验证
            $googleService = new \app\common\service\GoogleOAuthService();
            $socialUser = $googleService->verifyIdToken($accessToken);
        }

        if (!$socialUser || empty($socialUser['google_id'])) {
            return $this->error('Invalid Google token');
        }

        // 查找已绑定 Google 的用户
        $user = User::where('google_id', $socialUser['google_id'])->find();

        if (!$user) {
            // 如果有邮箱，检查邮箱是否已存在
            if (!empty($socialUser['email'])) {
                $existingUser = User::where('email', $socialUser['email'])->find();
                if ($existingUser) {
                    // 关联现有账户
                    $existingUser->google_id = $socialUser['google_id'];
                    if (empty($existingUser->oauth_provider)) {
                        $existingUser->oauth_provider = 'google';
                    }
                    $existingUser->save();
                    $user = $existingUser;
                }
            }

            // 如果还是没有用户，创建新用户
            if (!$user) {
                $isNewUser = true; // 标记为新用户

                $user = new User();
                $user->uuid = $this->generateUuid();
                $user->email = $socialUser['email'] ?: null;
                $user->nickname = $socialUser['name'] ?: ('User_' . substr($socialUser['google_id'], -6));
                $user->avatar = $socialUser['picture'] ?: null;
                $user->google_id = $socialUser['google_id'];
                $user->oauth_provider = 'google';
                $user->status = 1;
                $user->language = $this->locale;
                $user->currency = 'USD';
                $user->register_source = 'google';
                $user->save();

                // 首次 Google 登录 = 新用户注册，发送欢迎通知
                $this->sendWelcomeNotification($user);
            }
        }

        // 检查用户状态
        if ($user->status !== 1) {
            return $this->error('Account disabled');
        }

        // 更新登录信息
        $user->updateLoginInfo(request()->ip());

        // 生成 JWT Token
        $token = JWTAuth::builder(['uid' => $user->id]);

        return $this->success([
            'token' => $token,
            'refreshToken' => '',
            'user' => $user->toApiArray(),
        ], 'Login successful');
    }

    /**
     * 发送新用户欢迎通知（优惠券 + 游戏次数 + 邮件 + 站内信）
     * @param User $user
     */
    private function sendWelcomeNotification(User $user): void
    {
        // 发放新人优惠券
        $couponData = [];
        try {
            $couponService = new \app\common\service\NewUserCouponService();
            $couponData = $couponService->grantWelcomeCoupon($user->id, $this->locale) ?? [];
        } catch (\Exception $e) {
            \think\facade\Log::error('Failed to grant welcome coupon: ' . $e->getMessage());
        }

        // 发放新用户游戏次数（转盘 3 次）
        try {
            $gameChances = 3; // 新用户福利：3 次转盘机会
            \app\common\model\UserGameChance::addChances(
                $user->id,
                'wheel',
                $gameChances,
                'register',
                (string)$user->id,
                request()->ip()
            );
            \think\facade\Log::info("Granted {$gameChances} wheel chances to new user {$user->id}");
        } catch (\Exception $e) {
            \think\facade\Log::error('Failed to grant welcome game chances: ' . $e->getMessage());
        }

        // 发送欢迎通知（邮件 + 站内信）
        try {
            $notificationService = new \app\common\service\NotificationService();
            $notificationService->notifyUserRegistered($user->id, [
                'nickname' => $user->nickname,
                'email' => $user->email,
            ], $couponData);
        } catch (\Exception $e) {
            \think\facade\Log::error('Failed to send welcome notification: ' . $e->getMessage());
        }
    }

    /**
     * 退出登录
     * @return Response
     */
    public function logout(): Response
    {
        try {
            JWTAuth::invalidate(JWTAuth::token()->get());
        } catch (\Exception $e) {
            // 忽略错误
        }

        return $this->success([], 'Logout successful');
    }

    /**
     * 刷新 Token
     * @return Response
     */
    public function refresh(): Response
    {
        try {
            $token = JWTAuth::refresh();

            return $this->success([
                'token' => $token
            ]);

        } catch (\Exception $e) {
            return $this->error('Token refresh failed', 10001);
        }
    }

    /**
     * 检查登录是否被锁定
     * @param string $identifier 登录标识（邮箱或手机号）
     * @return true|array 未锁定返回true，锁定返回 ['key' => 翻译键, 'params' => 参数]
     */
    private function checkLoginLock(string $identifier)
    {
        $lockKey = 'login_lock:' . md5($identifier);
        $lockData = Cache::get($lockKey);

        if ($lockData) {
            $remainingMinutes = ceil(($lockData['lock_until'] - time()) / 60);
            if ($remainingMinutes > 0) {
                return [
                    'key' => 'Account is locked',
                    'params' => ['minutes' => $remainingMinutes]
                ];
            }
        }

        return true;
    }

    /**
     * 记录登录失败
     * @param string $identifier 登录标识
     * @return array 返回失败信息，包含 locked, key, params
     */
    private function recordLoginFailure(string $identifier): array
    {
        // 获取系统配置
        $maxAttempts = (int) SystemConfig::getConfig('login_max_attempts', 5);
        $lockMinutes = (int) SystemConfig::getConfig('login_lock_minutes', 30);

        $failKey = 'login_fail:' . md5($identifier);
        $lockKey = 'login_lock:' . md5($identifier);

        // 获取当前失败次数
        $failCount = (int) Cache::get($failKey, 0);
        $failCount++;

        // 保存失败次数（有效期为锁定时长的2倍，确保不会过早失效）
        Cache::set($failKey, $failCount, $lockMinutes * 60 * 2);

        $remainingAttempts = $maxAttempts - $failCount;

        // 检查是否需要锁定
        if ($failCount >= $maxAttempts) {
            // 锁定账号
            Cache::set($lockKey, [
                'lock_until' => time() + ($lockMinutes * 60),
                'attempts' => $failCount
            ], $lockMinutes * 60);

            // 清除失败计数
            Cache::delete($failKey);

            return [
                'locked' => true,
                'key' => 'Too many failed attempts',
                'params' => ['minutes' => $lockMinutes]
            ];
        }

        return [
            'locked' => false,
            'key' => 'Attempts remaining',
            'params' => ['count' => $remainingAttempts]
        ];
    }

    /**
     * 清除登录失败记录
     * @param string $identifier 登录标识
     */
    private function clearLoginFailure(string $identifier): void
    {
        $failKey = 'login_fail:' . md5($identifier);
        $lockKey = 'login_lock:' . md5($identifier);
        Cache::delete($failKey);
        Cache::delete($lockKey);
    }

    /**
     * 发送注册邮箱验证码
     * @return Response
     */
    public function sendRegisterEmailCode(): Response
    {
        $email = input('post.email', '');

        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return $this->error('Valid email address required');
        }

        // 检查邮箱是否已注册
        if (User::where('email', $email)->find()) {
            return $this->error('Email already registered');
        }

        // 检查发送频率限制（60秒内只能发送一次）
        $rateLimitKey = 'register_email_rate:' . md5($email);
        if (Cache::get($rateLimitKey)) {
            return $this->error('Please wait before requesting another code');
        }

        // 生成6位随机验证码
        $code = str_pad((string) mt_rand(0, 999999), 6, '0', STR_PAD_LEFT);

        // 存储验证码（有效期5分钟）
        $cacheKey = 'register_email_code:' . md5($email);
        Cache::set($cacheKey, [
            'code' => $code,
            'email' => $email,
            'created_at' => time()
        ], 300);

        // 设置发送频率限制（60秒）
        Cache::set($rateLimitKey, true, 60);

        // 通过邮件模板发送验证码
        try {
            $emailService = new EmailService();
            $locale = $this->locale ?? 'en-us';
            $result = $emailService->sendWithTemplate(
                $email,
                'email_verify',
                $locale,
                [
                    'code' => $code,
                    'nickname' => $email,
                    'expires_minutes' => '5',
                ]
            );
            if (!$result['success']) {
                throw new \Exception($result['error'] ?? 'Send failed');
            }
        } catch (\Throwable $e) {
            Cache::delete($cacheKey);
            Cache::delete($rateLimitKey);
            return $this->error('Failed to send verification email');
        }

        // 脱敏邮箱
        $parts = explode('@', $email);
        $name = $parts[0];
        $domain = $parts[1];
        $maskedName = substr($name, 0, 2) . str_repeat('*', max(1, strlen($name) - 2));
        $maskedEmail = $maskedName . '@' . $domain;

        return $this->success([
            'message' => 'Verification code sent',
            'expiresIn' => 300,
            'email' => $maskedEmail,
        ]);
    }

    /**
     * 验证注册邮箱验证码
     * @return Response
     */
    public function verifyRegisterEmailCode(): Response
    {
        $email = input('post.email', '');
        $code = input('post.code', '');

        if (empty($email) || empty($code)) {
            return $this->error('Email and code required');
        }

        $cacheKey = 'register_email_code:' . md5($email);
        $storedData = Cache::get($cacheKey);

        if (!$storedData) {
            return $this->error('Verification code expired or not found');
        }

        if ($storedData['code'] !== $code) {
            return $this->error('Invalid verification code');
        }

        // 验证成功，删除验证码
        Cache::delete($cacheKey);

        // 生成验证通过的临时 token
        $verifyToken = md5($email . time() . mt_rand());
        Cache::set('email_verified:' . $verifyToken, [
            'email' => $email,
            'verified_at' => time()
        ], 600); // 10分钟有效期

        return $this->success([
            'verified' => true,
            'verifyToken' => $verifyToken
        ]);
    }

    /**
     * 发送短信验证码（模拟）
     * @return Response
     */
    public function sendSmsCode(): Response
    {
        $phone = input('post.phone', '');
        $countryCode = input('post.countryCode', '');

        if (empty($phone)) {
            return $this->error('Phone number required');
        }

        // 检查发送频率限制（60秒内只能发送一次）
        $rateLimitKey = 'sms_rate:' . md5($countryCode . $phone);
        if (Cache::get($rateLimitKey)) {
            return $this->error('Please wait before requesting another code');
        }

        // 生成6位随机验证码
        $code = str_pad((string) mt_rand(0, 999999), 6, '0', STR_PAD_LEFT);

        // 存储验证码（有效期5分钟）
        $cacheKey = 'sms_code:' . md5($countryCode . $phone);
        Cache::set($cacheKey, [
            'code' => $code,
            'phone' => $phone,
            'countryCode' => $countryCode,
            'created_at' => time()
        ], 300);

        // 设置发送频率限制（60秒）
        Cache::set($rateLimitKey, true, 60);

        // 模拟发送延迟
        // 实际生产环境中这里会调用短信服务商API

        return $this->success([
            'code' => $code, // 模拟环境返回验证码，生产环境不返回
            'message' => 'Verification code sent',
            'expiresIn' => 300,
            'phone' => substr($phone, 0, 2) . str_repeat('x', max(0, strlen($phone) - 4)) . substr($phone, -2)
        ]);
    }

    /**
     * 验证短信验证码
     * @return Response
     */
    public function verifySmsCode(): Response
    {
        $phone = input('post.phone', '');
        $countryCode = input('post.countryCode', '');
        $code = input('post.code', '');

        if (empty($phone) || empty($code)) {
            return $this->error('Phone and code required');
        }

        $cacheKey = 'sms_code:' . md5($countryCode . $phone);
        $storedData = Cache::get($cacheKey);

        if (!$storedData) {
            return $this->error('Verification code expired or not found');
        }

        if ($storedData['code'] !== $code) {
            return $this->error('Invalid verification code');
        }

        // 验证成功，删除验证码
        Cache::delete($cacheKey);

        // 生成验证通过的临时token（用于注册时验证）
        $verifyToken = md5($countryCode . $phone . time() . mt_rand());
        Cache::set('phone_verified:' . $verifyToken, [
            'phone' => $phone,
            'countryCode' => $countryCode,
            'verified_at' => time()
        ], 600); // 10分钟有效期

        return $this->success([
            'verified' => true,
            'verifyToken' => $verifyToken
        ]);
    }

    /**
     * 完成注册（分步注册最后一步）
     * @return Response
     */
    public function completeRegister(): Response
    {
        $data = input('post.');

        // 验证必填字段
        $required = ['email', 'firstName', 'lastName', 'password', 'verifyToken'];
        foreach ($required as $field) {
            if (empty($data[$field])) {
                return $this->error("Field {$field} is required");
            }
        }

        // 验证邮箱验证 token（优先）或手机号验证 token（兼容旧版）
        $verifyData = Cache::get('email_verified:' . $data['verifyToken']);
        $verifyType = 'email';
        if (!$verifyData) {
            $verifyData = Cache::get('phone_verified:' . $data['verifyToken']);
            $verifyType = 'phone';
        }
        if (!$verifyData) {
            return $this->error('Verification expired, please verify again');
        }

        // 验证密码强度
        $pwd = $data['password'];
        if (strlen($pwd) <= 8) {
            return $this->error('Password must be more than 8 characters');
        }
        if (!preg_match('/[a-z]/', $pwd)) {
            return $this->error('Password must contain at least one lowercase letter');
        }
        if (!preg_match('/[A-Z]/', $pwd)) {
            return $this->error('Password must contain at least one uppercase letter');
        }
        if (!preg_match('/[^a-zA-Z0-9]/', $pwd)) {
            return $this->error('Password must contain at least one special character');
        }

        // 检查邮箱是否已存在
        if (User::where('email', $data['email'])->find()) {
            return $this->error('Email already registered');
        }

        // 检查手机号是否已存在（仅手机号验证方式）
        $fullPhone = '';
        if ($verifyType === 'phone' && !empty($verifyData['phone'])) {
            $fullPhone = $verifyData['countryCode'] . $verifyData['phone'];
            if (User::where('phone', $fullPhone)->find()) {
                return $this->error('Phone number already registered');
            }
        }

        // 组合 nickname
        $nickname = trim($data['firstName'] . ' ' . $data['lastName']);
        if (empty($nickname)) {
            return $this->error('First name and last name cannot both be empty');
        }

        Db::startTrans();
        try {
            // 创建用户
            $user = new User();
            $user->uuid = $this->generateUuid();
            $user->email = $data['email'];
            if ($fullPhone) {
                $user->phone = $fullPhone;
                $user->phone_country_code = $verifyData['countryCode'] ?? '';
                $user->phone_verified = 1;
            }
            $user->plain_password = $data['password'];
            $user->password = $data['password'];
            $user->nickname = $nickname;
            $user->first_name = $data['firstName'];
            $user->last_name = $data['lastName'];
            $user->language = $this->locale;
            $user->currency = 'USD';
            $user->status = 1;
            $user->register_source = 'app';
            $user->email_verified = ($verifyType === 'email') ? 1 : 0;
            $user->save();

            // 删除验证 token
            Cache::delete('email_verified:' . $data['verifyToken']);
            Cache::delete('phone_verified:' . $data['verifyToken']);

            // 生成 Token
            $token = JWTAuth::builder(['uid' => $user->id]);

            Db::commit();

            // 发送欢迎通知（优惠券 + 邮件 + 站内信）
            $this->sendWelcomeNotification($user);

            return $this->success([
                'token' => $token,
                'refreshToken' => '',
                'user' => $user->toApiArray()
            ], 'Register successful');

        } catch (\Exception $e) {
            Db::rollback();
            return $this->error('Register failed: ' . $e->getMessage());
        }
    }

    /**
     * 发送密码重置验证码
     * @return Response
     */
    public function sendResetCode(): Response
    {
        $email = input('post.email', '');

        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return $this->error('Valid email is required');
        }

        // 检查用户是否存在
        $user = User::where('email', $email)->find();
        if (!$user) {
            // 为了安全，不透露用户是否存在，仍然返回成功
            return $this->success([
                'message' => 'If this email exists, you will receive a reset code',
                'expiresIn' => 300,
                'email' => substr($email, 0, 3) . '***' . substr($email, strpos($email, '@')),
            ]);
        }

        // 检查发送频率（60秒内只能发送一次）
        $rateLimitKey = 'reset_rate:' . md5($email);
        if (Cache::get($rateLimitKey)) {
            return $this->error('Please wait before requesting another code');
        }

        // 生成验证码
        $code = PasswordReset::generateCode();
        $expiresAt = date('Y-m-d H:i:s', time() + 300); // 5分钟有效

        // 保存到数据库
        PasswordReset::create([
            'email' => $email,
            'code' => $code,
            'expires_at' => $expiresAt,
        ]);

        // 设置频率限制（60秒）
        Cache::set($rateLimitKey, true, 60);

        // 发送邮件
        try {
            $emailService = new EmailService();
            $emailService->sendWithTemplate(
                $email,
                'password_reset',
                $this->locale,
                [
                    'code' => $code,
                    'nickname' => $user->nickname ?: 'User',
                    'expires_minutes' => 5,
                ],
                ['to_name' => $user->nickname, 'user_id' => $user->id]
            );
        } catch (\Exception $e) {
            \think\facade\Log::error('Failed to send password reset email: ' . $e->getMessage());
            // 即使邮件发送失败，也不告知用户，避免泄露信息
        }

        return $this->success([
            'message' => 'Reset code sent',
            'expiresIn' => 300,
            'email' => substr($email, 0, 3) . '***' . substr($email, strpos($email, '@')),
            'code' => $code, // 开发环境返回验证码，生产环境应删除
        ]);
    }

    /**
     * 验证密码重置验证码
     * @return Response
     */
    public function verifyResetCode(): Response
    {
        $email = input('post.email', '');
        $code = input('post.code', '');

        if (empty($email) || empty($code)) {
            return $this->error('Email and code are required');
        }

        // 调试：记录收到的参数
        \think\facade\Log::info('verifyResetCode - email: ' . $email . ', code: ' . $code);

        // 查找有效的验证码记录
        $record = PasswordReset::where('email', $email)
            ->where('code', $code)
            ->where('used', 0)
            ->where('expires_at', '>', date('Y-m-d H:i:s'))
            ->order('id', 'desc')
            ->find();

        // 调试：如果没找到，检查原因
        if (!$record) {
            // 检查是否有该邮箱的记录
            $anyRecord = PasswordReset::where('email', $email)->order('id', 'desc')->find();
            if ($anyRecord) {
                \think\facade\Log::info('verifyResetCode - Found record for email, stored code: ' . $anyRecord->code . ', used: ' . $anyRecord->used . ', expires_at: ' . $anyRecord->expires_at . ', now: ' . date('Y-m-d H:i:s'));
            } else {
                \think\facade\Log::info('verifyResetCode - No record found for email: ' . $email);
            }
            return $this->error('Invalid or expired code');
        }

        // 生成重置令牌
        $token = PasswordReset::generateToken();
        $record->token = $token;
        $record->save();

        return $this->success([
            'verified' => true,
            'resetToken' => $token,
        ]);
    }

    /**
     * 重置密码
     * @return Response
     */
    public function resetPassword(): Response
    {
        $resetToken = input('post.resetToken', '');
        $password = input('post.password', '');
        $confirmPassword = input('post.confirmPassword', '');

        if (empty($resetToken) || empty($password)) {
            return $this->error('Reset token and password are required');
        }

        if ($password !== $confirmPassword) {
            return $this->error('Passwords do not match');
        }

        if (strlen($password) <= 8) {
            return $this->error('Password must be more than 8 characters');
        }
        if (!preg_match('/[a-z]/', $password)) {
            return $this->error('Password must contain at least one lowercase letter');
        }
        if (!preg_match('/[A-Z]/', $password)) {
            return $this->error('Password must contain at least one uppercase letter');
        }
        if (!preg_match('/[^a-zA-Z0-9]/', $password)) {
            return $this->error('Password must contain at least one special character');
        }

        // 查找有效的重置记录
        $record = PasswordReset::where('token', $resetToken)
            ->where('used', 0)
            ->where('expires_at', '>', date('Y-m-d H:i:s'))
            ->find();

        if (!$record) {
            return $this->error('Invalid or expired reset token');
        }

        // 查找用户
        $user = User::where('email', $record->email)->find();
        if (!$user) {
            return $this->error('User not found');
        }

        // 更新密码
        $user->plain_password = $password;
        $user->password = $password; // 自动触发 bcrypt 加密
        $user->save();

        // 标记为已使用
        $record->used = 1;
        $record->save();

        return $this->success(['message' => 'Password reset successfully']);
    }
}
