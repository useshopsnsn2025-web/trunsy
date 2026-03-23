<?php
declare(strict_types=1);

namespace app\api\middleware;

use think\Response;
use think\facade\Lang;
use think\facade\Cache;
use app\common\model\Language as LanguageModel;

/**
 * 多语言中间件
 */
class Language
{
    /**
     * 支持的语言列表（从数据库动态获取）
     * @var array|null
     */
    protected $supported = null;

    /**
     * 默认语言
     * @var string
     */
    protected $default = 'en-us';

    /**
     * 获取支持的语言列表（从数据库，带缓存）
     * @return array
     */
    protected function getSupportedLanguages(): array
    {
        if ($this->supported !== null) {
            return $this->supported;
        }

        // 从缓存获取
        $cacheKey = 'supported_languages';
        $this->supported = Cache::get($cacheKey);

        if ($this->supported === null) {
            try {
                // 从数据库获取所有启用的语言代码
                $this->supported = LanguageModel::where('is_active', 1)
                    ->column('code');

                // 确保至少有默认语言
                if (empty($this->supported)) {
                    $this->supported = ['en-us'];
                }

                // 缓存 1 小时
                Cache::set($cacheKey, $this->supported, 3600);
            } catch (\Exception $e) {
                // 数据库错误时使用默认值
                $this->supported = ['zh-tw', 'en-us', 'ja-jp'];
            }
        }

        return $this->supported;
    }

    /**
     * 处理请求
     * @param \think\Request $request
     * @param \Closure $next
     * @return Response
     */
    public function handle($request, \Closure $next): Response
    {
        // 优先级: Header > Query > User Preference > Default
        $locale = $this->getLocaleFromHeader($request)
            ?? $request->param('lang')
            ?? $this->getUserLanguage($request)
            ?? $this->default;

        // 标准化语言代码
        $locale = $this->normalizeLocale($locale);

        // 获取动态支持的语言列表
        $supportedLanguages = $this->getSupportedLanguages();

        // 验证语言
        if (!in_array($locale, $supportedLanguages)) {
            $locale = $this->default;
        }

        // 设置应用语言
        Lang::setLangSet($locale);

        // 将语言注入请求
        $request->locale = $locale;

        return $next($request);
    }

    /**
     * 从 Header 获取语言
     * @param \think\Request $request
     * @return string|null
     */
    protected function getLocaleFromHeader($request): ?string
    {
        $acceptLanguage = $request->header('Accept-Language');

        if (!$acceptLanguage) {
            return null;
        }

        // 解析 Accept-Language header
        // 格式: zh-CN,zh;q=0.9,en;q=0.8
        $parts = explode(',', $acceptLanguage);
        if (!empty($parts[0])) {
            return trim(explode(';', $parts[0])[0]);
        }

        return null;
    }

    /**
     * 获取用户语言偏好
     * @param \think\Request $request
     * @return string|null
     */
    protected function getUserLanguage($request): ?string
    {
        // 如果已经通过认证中间件获取了用户信息
        if (isset($request->user) && $request->user) {
            return $request->user->language ?? null;
        }

        return null;
    }

    /**
     * 标准化语言代码
     * @param string $locale
     * @return string
     */
    protected function normalizeLocale(string $locale): string
    {
        $locale = strtolower(trim($locale));

        // 语言代码映射 (简体中文映射到繁体中文)
        $map = [
            // 繁体中文
            'zh-hant' => 'zh-tw',
            'zh-tw' => 'zh-tw',
            'zh-hk' => 'zh-tw',
            'zh-mo' => 'zh-tw',
            // 简体中文也映射到繁体（因为移除了简体）
            'zh' => 'zh-tw',
            'zh-hans' => 'zh-tw',
            'zh-cn' => 'zh-tw',
            // 英文
            'en' => 'en-us',
            'en-us' => 'en-us',
            'en-gb' => 'en-us',
            'en-au' => 'en-us',
            'en-ca' => 'en-us',
            // 日文
            'ja' => 'ja-jp',
            'ja-jp' => 'ja-jp',
            // 韩文
            'ko' => 'ko-kr',
            'ko-kr' => 'ko-kr',
        ];

        return $map[$locale] ?? $locale;
    }
}
