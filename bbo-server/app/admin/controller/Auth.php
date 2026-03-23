<?php
declare(strict_types=1);

namespace app\admin\controller;

use think\Response;
use think\facade\Cache;
use app\common\model\Admin;
use app\common\model\SystemConfig;

/**
 * 管理后台认证控制器
 */
class Auth extends Base
{
    /**
     * Token有效期（秒）
     */
    const TOKEN_EXPIRE = 86400 * 7; // 7天

    /**
     * 管理员登录
     * @return Response
     */
    public function login(): Response
    {
        $username = input('post.username', '');
        $password = input('post.password', '');

        if (empty($username) || empty($password)) {
            return $this->error('请输入用户名和密码');
        }

        // 检查是否被锁定
        $lockCheck = $this->checkLoginLock($username);
        if ($lockCheck !== true) {
            return $this->error($lockCheck);
        }

        // 查找管理员
        $admin = Admin::where('username', $username)->find();
        if (!$admin) {
            // 记录失败次数
            $this->recordLoginFailure($username);
            return $this->error('用户名或密码错误');
        }

        // 验证密码
        if (!$admin->verifyPassword($password)) {
            // 记录失败次数
            $failInfo = $this->recordLoginFailure($username);
            if ($failInfo['locked']) {
                return $this->error($failInfo['message']);
            }
            return $this->error('用户名或密码错误，' . $failInfo['message']);
        }

        // 检查状态
        if ($admin->status !== 1) {
            return $this->error('账号已被禁用');
        }

        // 登录成功，清除失败记录
        $this->clearLoginFailure($username);

        // 生成Token
        $token = $this->generateToken($admin->id);

        // 缓存Token
        Cache::set('admin_token:' . $token, [
            'admin_id' => $admin->id,
            'username' => $admin->username,
            'login_time' => time(),
        ], self::TOKEN_EXPIRE);

        // 更新登录信息
        $admin->last_login_at = date('Y-m-d H:i:s');
        $admin->last_login_ip = request()->ip();
        $admin->save();

        // 获取角色信息
        $role = $admin->role;

        return $this->success([
            'token' => $token,
            'expires_in' => self::TOKEN_EXPIRE,
            'admin' => [
                'id' => $admin->id,
                'username' => $admin->username,
                'nickname' => $admin->nickname,
                'avatar' => $admin->avatar,
                'email' => $admin->email,
                'role' => $role ? [
                    'id' => $role->id,
                    'name' => $role->name,
                    'code' => $role->code,
                    'permissions' => $role->permissions,
                ] : null,
            ],
        ], '登录成功');
    }

    /**
     * 退出登录
     * @return Response
     */
    public function logout(): Response
    {
        $token = $this->getToken();
        if ($token) {
            Cache::delete('admin_token:' . $token);
        }
        return $this->success([], '退出成功');
    }

    /**
     * 获取当前登录信息
     * @return Response
     */
    public function info(): Response
    {
        $token = $this->getToken();
        if (!$token) {
            return $this->error('未登录', 401);
        }

        $tokenData = Cache::get('admin_token:' . $token);
        if (!$tokenData) {
            return $this->error('登录已过期', 401);
        }

        $admin = Admin::with(['role'])->find($tokenData['admin_id']);
        if (!$admin) {
            return $this->error('用户不存在', 401);
        }

        if ($admin->status !== 1) {
            return $this->error('账号已被禁用', 401);
        }

        $role = $admin->role;

        return $this->success([
            'id' => $admin->id,
            'username' => $admin->username,
            'nickname' => $admin->nickname,
            'avatar' => $admin->avatar,
            'email' => $admin->email,
            'phone' => $admin->phone,
            'role' => $role ? [
                'id' => $role->id,
                'name' => $role->name,
                'code' => $role->code,
                'permissions' => $role->permissions,
            ] : null,
            'last_login_at' => $admin->last_login_at,
            'last_login_ip' => $admin->last_login_ip,
        ]);
    }

    /**
     * 修改密码
     * @return Response
     */
    public function changePassword(): Response
    {
        $token = $this->getToken();
        if (!$token) {
            return $this->error('未登录', 401);
        }

        $tokenData = Cache::get('admin_token:' . $token);
        if (!$tokenData) {
            return $this->error('登录已过期', 401);
        }

        $oldPassword = input('post.old_password', '');
        $newPassword = input('post.new_password', '');

        if (empty($oldPassword) || empty($newPassword)) {
            return $this->error('请输入原密码和新密码');
        }

        if (strlen($newPassword) < 6) {
            return $this->error('新密码长度不能少于6位');
        }

        $admin = Admin::find($tokenData['admin_id']);
        if (!$admin) {
            return $this->error('用户不存在');
        }

        if (!$admin->verifyPassword($oldPassword)) {
            return $this->error('原密码错误');
        }

        $admin->password = $newPassword;
        $admin->save();

        // 清除Token，需要重新登录
        Cache::delete('admin_token:' . $token);

        return $this->success([], '密码修改成功，请重新登录');
    }

    /**
     * 生成Token
     * @param int $adminId
     * @return string
     */
    private function generateToken(int $adminId): string
    {
        return md5($adminId . time() . mt_rand(100000, 999999) . uniqid());
    }

    /**
     * 从请求头获取Token
     * @return string|null
     */
    private function getToken(): ?string
    {
        $authorization = request()->header('Authorization', '');
        if (preg_match('/Bearer\s+(.+)$/i', $authorization, $matches)) {
            return $matches[1];
        }
        return input('token', null);
    }

    /**
     * 检查登录是否被锁定
     * @param string $username 用户名
     * @return true|string 未锁定返回true，锁定返回错误信息
     */
    private function checkLoginLock(string $username)
    {
        $lockKey = 'admin_login_lock:' . md5($username);
        $lockData = Cache::get($lockKey);

        if ($lockData) {
            $remainingMinutes = ceil(($lockData['lock_until'] - time()) / 60);
            if ($remainingMinutes > 0) {
                return "账号已被锁定，请在 {$remainingMinutes} 分钟后重试";
            }
        }

        return true;
    }

    /**
     * 记录登录失败
     * @param string $username 用户名
     * @return array 返回失败信息
     */
    private function recordLoginFailure(string $username): array
    {
        // 获取系统配置
        $maxAttempts = (int) SystemConfig::getConfig('login_max_attempts', 5);
        $lockMinutes = (int) SystemConfig::getConfig('login_lock_minutes', 30);

        $failKey = 'admin_login_fail:' . md5($username);
        $lockKey = 'admin_login_lock:' . md5($username);

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
                'message' => "登录失败次数过多，账号已被锁定 {$lockMinutes} 分钟"
            ];
        }

        return [
            'locked' => false,
            'message' => "还剩 {$remainingAttempts} 次尝试机会"
        ];
    }

    /**
     * 清除登录失败记录
     * @param string $username 用户名
     */
    private function clearLoginFailure(string $username): void
    {
        $failKey = 'admin_login_fail:' . md5($username);
        $lockKey = 'admin_login_lock:' . md5($username);
        Cache::delete($failKey);
        Cache::delete($lockKey);
    }
}
