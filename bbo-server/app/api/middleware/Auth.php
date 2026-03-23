<?php
declare(strict_types=1);

namespace app\api\middleware;

use think\Response;
use thans\jwt\facade\JWTAuth;
use thans\jwt\exception\TokenExpiredException;
use thans\jwt\exception\TokenInvalidException;
use thans\jwt\exception\JWTException;
use app\common\model\User;

/**
 * JWT 认证中间件
 */
class Auth
{
    /**
     * 处理请求
     * @param \think\Request $request
     * @param \Closure $next
     * @return Response
     */
    public function handle($request, \Closure $next): Response
    {
        try {
            // 验证 Token
            $payload = JWTAuth::auth();

            // 获取用户ID - payload 可能是数组或对象，值可能是 Claim 对象
            if (is_array($payload)) {
                $uidValue = $payload['uid'] ?? null;
            } else {
                $uidValue = $payload->get('uid');
            }

            // Lcobucci JWT 库返回的是 Claim 对象，需要调用 getValue() 获取实际值
            if (is_object($uidValue) && method_exists($uidValue, 'getValue')) {
                $userId = $uidValue->getValue();
            } else {
                $userId = $uidValue;
            }

            // 确保 userId 是整数
            $userId = is_numeric($userId) ? (int) $userId : null;

            if (!$userId) {
                return $this->unauthorized('Token invalid');
            }

            // 获取用户信息
            $user = User::find($userId);

            if (!$user) {
                return $this->unauthorized('User not found');
            }

            if ($user->status !== 1) {
                return $this->unauthorized('User disabled');
            }

            // 将用户信息注入请求
            $request->userId = $userId;
            $request->user = $user;

            return $next($request);

        } catch (TokenExpiredException $e) {
            return $this->unauthorized('Token expired', 10001);
        } catch (TokenInvalidException $e) {
            return $this->unauthorized('Token invalid', 10002);
        } catch (JWTException $e) {
            return $this->unauthorized('Token required', 10003);
        }
    }

    /**
     * 返回未授权响应
     * @param string $msg
     * @param int $code
     * @return Response
     */
    protected function unauthorized(string $msg, int $code = 401): Response
    {
        return json([
            'code' => $code,
            'msg' => lang($msg),
            'data' => null
        ], 401);
    }
}
