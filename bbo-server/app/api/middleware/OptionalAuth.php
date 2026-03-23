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
 * 可选 JWT 认证中间件
 * 尝试解析用户身份，但不强制要求认证
 */
class OptionalAuth
{
    /**
     * 处理请求
     * @param \think\Request $request
     * @param \Closure $next
     * @return Response
     */
    public function handle($request, \Closure $next): Response
    {
        // 默认为空
        $request->userId = null;
        $request->user = null;

        try {
            // 检查是否有 Authorization header
            $authorization = $request->header('Authorization');
            if (!$authorization) {
                return $next($request);
            }

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

            if ($userId) {
                // 获取用户信息
                $user = User::find($userId);

                if ($user && $user->status === 1) {
                    // 将用户信息注入请求
                    $request->userId = $userId;
                    $request->user = $user;
                }
            }

        } catch (TokenExpiredException $e) {
            // Token 过期，忽略
        } catch (TokenInvalidException $e) {
            // Token 无效，忽略
        } catch (JWTException $e) {
            // JWT 异常，忽略
        }

        return $next($request);
    }
}
