<?php
declare(strict_types=1);

namespace app\common\provider;

use thans\jwt\facade\JWTAuth;
use thans\jwt\parser\AuthHeader;
use thans\jwt\parser\Cookie;
use thans\jwt\parser\Param;
use think\App;
use think\Container;
use think\facade\Config;
use think\Request;
use Lcobucci\JWT\Builder;
use Lcobucci\JWT\Parser;
use app\common\service\JwtConfig;

/**
 * 自定义 JWT Provider
 * 支持从数据库读取 TTL 配置
 */
class JwtProvider
{
    private $request;
    private $config;

    public function __construct(Request $request)
    {
        $this->request = $request;
        $config = require dirname(__DIR__, 3) . '/vendor/thans/tp-jwt-auth/config/config.php';

        if (strpos(App::VERSION, '6.') === 0) {
            $this->config = array_merge($config, Config::get('jwt') ?? []);
        } else {
            $this->config = array_merge($config, Config::get('jwt.') ?? []);
        }

        // 从数据库/服务获取 TTL 配置
        $this->loadDynamicConfig();
    }

    /**
     * 从数据库加载动态配置
     */
    protected function loadDynamicConfig(): void
    {
        try {
            $this->config['ttl'] = JwtConfig::getTtl();
            $this->config['refresh_ttl'] = JwtConfig::getRefreshTtl();
        } catch (\Exception $e) {
            // 如果数据库不可用，使用默认配置
        }
    }

    protected function registerBlacklist()
    {
        Container::getInstance()->make('thans\jwt\Blacklist', [
            new $this->config['blacklist_storage'],
        ])->setRefreshTTL($this->config['refresh_ttl'])->setGracePeriod($this->config['blacklist_grace_period']);
    }

    protected function registerProvider()
    {
        $keys = $this->config['secret']
            ? $this->config['secret']
            : [
                'public'   => $this->config['public_key'],
                'private'  => $this->config['private_key'],
                'password' => $this->config['password'],
            ];
        Container::getInstance()->make('thans\jwt\provider\JWT\Lcobucci', [
            new Builder(),
            new Parser(),
            $this->config['algo'],
            $keys,
        ]);
    }

    protected function registerFactory()
    {
        Container::getInstance()->make('thans\jwt\claim\Factory', [
            new Request(),
            $this->config['ttl'],
            $this->config['refresh_ttl'],
        ]);
    }

    protected function registerPayload()
    {
        Container::getInstance()->make('thans\jwt\Payload', [
            Container::getInstance()->make('thans\jwt\claim\Factory'),
        ]);
    }

    protected function registerManager()
    {
        Container::getInstance()->make('thans\jwt\Manager', [
            Container::getInstance()->make('thans\jwt\Blacklist'),
            Container::getInstance()->make('thans\jwt\Payload'),
            Container::getInstance()->make('thans\jwt\provider\JWT\Lcobucci'),
        ]);
    }

    protected function registerJWTAuth()
    {
        $chains = [
            'header' => new AuthHeader(),
            'cookie' => new Cookie(),
            'param'  => new Param()
        ];

        $mode = $this->config['token_mode'];
        $setChain = [];

        foreach ($mode as $key => $chain) {
            if (isset($chains[$chain])) {
                $setChain[$key] = $chains[$chain];
            }
        }

        JWTAuth::parser()->setRequest($this->request)->setChain($setChain);
    }

    public function init()
    {
        $this->registerBlacklist();
        $this->registerProvider();
        $this->registerFactory();
        $this->registerPayload();
        $this->registerManager();
        $this->registerJWTAuth();
    }
}
