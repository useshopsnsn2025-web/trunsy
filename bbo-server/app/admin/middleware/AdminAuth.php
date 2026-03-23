<?php
declare(strict_types=1);

namespace app\admin\middleware;

use think\Response;
use think\facade\Cache;
use app\common\model\Admin;

/**
 * 管理后台认证中间件
 */
class AdminAuth
{
    /**
     * 处理请求
     * @param \think\Request $request
     * @param \Closure $next
     * @return Response
     */
    public function handle($request, \Closure $next): Response
    {
        // 从请求头获取Token
        $token = $this->getToken($request);

        if (!$token) {
            return $this->unauthorized('请先登录');
        }

        // 从缓存获取Token信息
        $tokenData = Cache::get('admin_token:' . $token);

        if (!$tokenData) {
            return $this->unauthorized('登录已过期，请重新登录');
        }

        // 获取管理员信息
        $admin = Admin::with(['role'])->find($tokenData['admin_id']);

        if (!$admin) {
            Cache::delete('admin_token:' . $token);
            return $this->unauthorized('用户不存在');
        }

        if ($admin->status !== 1) {
            Cache::delete('admin_token:' . $token);
            return $this->unauthorized('账号已被禁用');
        }

        // 将管理员信息注入请求
        $request->adminId = $admin->id;
        $request->admin = $admin;
        $request->adminRole = $admin->role;

        return $next($request);
    }

    /**
     * 从请求头获取Token
     * @param \think\Request $request
     * @return string|null
     */
    protected function getToken($request): ?string
    {
        $authorization = $request->header('Authorization', '');
        if (preg_match('/Bearer\s+(.+)$/i', $authorization, $matches)) {
            return $matches[1];
        }
        return $request->param('token', null);
    }

    /**
     * 返回未授权响应
     * @param string $msg
     * @return Response
     */
    protected function unauthorized(string $msg): Response
    {
        return json([
            'code' => 401,
            'msg' => $msg,
            'data' => null
        ], 401);
    }
}
