<?php
declare(strict_types=1);

namespace app\common\middleware;

use think\Request;
use app\common\provider\JwtProvider;

/**
 * 自定义 JWT 注入中间件
 * 支持从数据库读取 TTL 配置
 */
class InjectJwt
{
    public function handle(Request $request, $next)
    {
        (new JwtProvider($request))->init();
        $response = $next($request);
        return $response;
    }
}
