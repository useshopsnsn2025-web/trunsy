<?php
declare (strict_types = 1);

namespace app;

use think\Service;
use app\common\middleware\InjectJwt;

/**
 * 应用服务类
 */
class AppService extends Service
{
    public function register()
    {
        // 服务注册
    }

    public function boot()
    {
        // 添加自定义 JWT 中间件（支持从数据库读取 TTL 配置）
        // 这个中间件会覆盖 thans\jwt 的默认配置
        $this->app->middleware->add(InjectJwt::class);
    }
}
