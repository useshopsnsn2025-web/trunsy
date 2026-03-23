<?php
declare(strict_types=1);

namespace app\api\controller;

use think\Response;
use think\facade\Db;
use think\facade\Cache;
use app\common\model\SystemConfig;
use app\common\model\UiTranslation;
use app\common\model\Currency;
use app\common\model\Country;
use app\common\model\SellFaq;
use app\common\model\Banner;
use app\common\helper\UrlHelper;

/**
 * 系统控制器
 */
class System extends Base
{
    /**
     * 获取支持的语言列表
     * @return Response
     */
    public function languages(): Response
    {
        $languages = Db::table('languages')
            ->where('is_active', 1)
            ->order('sort', 'asc')
            ->field('code, name, native_name as nativeName, flag, is_default as isDefault')
            ->select()
            ->toArray();

        // 转换布尔值
        foreach ($languages as &$lang) {
            $lang['isDefault'] = (bool) $lang['isDefault'];
        }

        return $this->success($languages);
    }

    /**
     * 获取系统配置
     * @return Response
     */
    public function config(): Response
    {
        // 从数据库获取基础配置（支持多语言）
        $siteName = SystemConfig::getConfigTranslated('site_name', $this->locale, 'TURNSY');
        $siteLogo = SystemConfig::getConfig('site_logo', '');
        $contactEmail = SystemConfig::getConfig('contact_email', '');
        $contactPhone = SystemConfig::getConfig('contact_phone', '');
        $contactAddress = SystemConfig::getConfigTranslated('contact_address', $this->locale, '');

        // 转换 LOGO URL 为当前域名
        $siteLogo = UrlHelper::convertImageUrl($siteLogo);

        $apkDownloadUrl = SystemConfig::getConfig('apk_download_url', '');
        if ($apkDownloadUrl) {
            $apkDownloadUrl = UrlHelper::getFullUrl($apkDownloadUrl);
        }

        $config = [
            'appName' => $siteName,
            'appLogo' => $siteLogo,
            'appVersion' => '1.0.0',
            'apkDownloadUrl' => $apkDownloadUrl,
            'contactEmail' => $contactEmail,
            'contactPhone' => $contactPhone,
            'contactAddress' => $contactAddress,
            'supportedLocales' => ['zh-CN', 'zh-TW', 'en-US'],
            'defaultLocale' => 'en-US',
            'supportedCurrencies' => ['USD', 'CNY', 'HKD', 'TWD', 'EUR', 'GBP'],
            'defaultCurrency' => 'USD',
            'paymentMethods' => ['paypal', 'stripe'],
            'goodsConditions' => [
                ['value' => 1, 'label' => 'Brand New'],
                ['value' => 2, 'label' => 'Like New'],
                ['value' => 3, 'label' => 'Lightly Used'],
                ['value' => 4, 'label' => 'Well Used'],
                ['value' => 5, 'label' => 'Has Defects'],
            ],
            'maxImages' => 9,
            'maxVideoSize' => 50 * 1024 * 1024, // 50MB
            'maxImageSize' => 5 * 1024 * 1024,  // 5MB
        ];

        return $this->success($config);
    }

    /**
     * 获取汇率列表
     * @return Response
     */
    public function exchangeRates(): Response
    {
        $rates = [];

        // 从数据库获取汇率配置
        $configs = Db::table('system_configs')
            ->where('group', 'currency')
            ->where('key', 'like', 'rate_%')
            ->field('`key`, value')
            ->select()
            ->toArray();

        foreach ($configs as $config) {
            // 提取货币代码 (rate_USD -> USD)
            $currency = str_replace('rate_', '', $config['key']);
            $rates[$currency] = (float) $config['value'];
        }

        // 获取基准货币
        $baseCurrency = SystemConfig::getConfig('base_currency', 'USD');

        return $this->success([
            'baseCurrency' => $baseCurrency,
            'rates' => $rates,
        ]);
    }

    /**
     * 获取 UI 翻译
     * @return Response
     */
    public function uiTranslations(): Response
    {
        $locale = input('locale', $this->locale);

        // 标准化语言代码（zh-TW -> zh-tw）
        $locale = strtolower($locale);

        // 缓存键
        $cacheKey = 'ui_translations:' . $locale;

        // 尝试从缓存获取
        $translations = Cache::get($cacheKey);

        if (!$translations) {
            // 从数据库获取
            $translations = UiTranslation::getTranslationsByLocale($locale);

            // 如果目标语言没有翻译，尝试回退到英语
            if (empty($translations)) {
                $translations = UiTranslation::getTranslationsByLocale('en-us');
            }

            // 缓存 1 小时
            if (!empty($translations)) {
                Cache::set($cacheKey, $translations, 3600);
            }
        }

        // 返回翻译数据和版本号（用于客户端缓存判断）
        return $this->success([
            'locale' => $locale,
            'version' => md5(json_encode($translations)),
            'translations' => $translations,
        ]);
    }

    /**
     * 检查 UI 翻译版本（用于客户端判断是否需要更新）
     * @return Response
     */
    public function uiTranslationsVersion(): Response
    {
        $locale = input('locale', $this->locale);
        $locale = strtolower($locale);

        $cacheKey = 'ui_translations:' . $locale;
        $translations = Cache::get($cacheKey);

        if (!$translations) {
            $translations = UiTranslation::getTranslationsByLocale($locale);
            if (empty($translations)) {
                $translations = UiTranslation::getTranslationsByLocale('en-us');
            }
        }

        return $this->success([
            'locale' => $locale,
            'version' => md5(json_encode($translations)),
        ]);
    }

    /**
     * 获取货币列表
     * @return Response
     */
    public function currencies(): Response
    {
        $cacheKey = 'app_currencies';
        $currencies = Cache::get($cacheKey);

        if (!$currencies) {
            $currencies = Currency::getActiveCurrencies();
            // 缓存 1 小时
            Cache::set($cacheKey, $currencies, 3600);
        }

        return $this->success($currencies);
    }

    /**
     * 获取国家/地区列表（带翻译和货币信息）
     * @return Response
     */
    public function countries(): Response
    {
        $locale = strtolower($this->locale);

        $cacheKey = 'app_countries:' . $locale;
        $countries = Cache::get($cacheKey);

        if (!$countries) {
            $countries = Country::getListForApp($locale);
            // 缓存 1 小时
            Cache::set($cacheKey, $countries, 3600);
        }

        return $this->success($countries);
    }

    /**
     * 获取 OAuth 配置（供前端使用）
     * 注意：只返回公开配置，不返回敏感信息如 client_secret
     * @return Response
     */
    public function oauthConfig(): Response
    {
        $config = [
            'google' => [
                'enabled' => SystemConfig::getConfig('google_oauth_enabled', '0') === '1',
                // 通用 Client ID（向后兼容，优先使用 Web Client ID）
                'clientId' => SystemConfig::getConfig('google_client_id_web', '')
                    ?: SystemConfig::getConfig('google_client_id', ''),
                // 各平台专用 Client ID
                'clientIdWeb' => SystemConfig::getConfig('google_client_id_web', ''),
                'clientIdAndroid' => SystemConfig::getConfig('google_client_id_android', ''),
                'clientIdIos' => SystemConfig::getConfig('google_client_id_ios', ''),
            ],
            'apple' => [
                'enabled' => SystemConfig::getConfig('apple_oauth_enabled', '0') === '1',
                'clientId' => SystemConfig::getConfig('apple_client_id', ''),
            ],
        ];

        return $this->success($config);
    }

    /**
     * 获取默认国家/语言配置
     * 用于首次访问时确定默认语言和货币
     * @return Response
     */
    public function defaultCountry(): Response
    {
        $defaultCode = SystemConfig::getConfig('default_country_code', 'TW');
        $country = Country::where('code', $defaultCode)->where('is_active', 1)->find();

        if (!$country) {
            // 如果默认国家不存在或未启用，返回第一个启用的国家
            $country = Country::where('is_active', 1)->order('sort', 'asc')->find();
        }

        if (!$country) {
            // 如果没有任何启用的国家，返回默认值
            return $this->success([
                'code' => 'TW',
                'locale' => 'zh-tw',
                'currency' => 'TWD',
            ]);
        }

        return $this->success([
            'code' => $country->code,
            'name' => $country->name,
            'locale' => $country->locale ?: 'en-us',
            'currency' => $country->currency_code,
            'flag' => $country->flag,
        ]);
    }

    /**
     * 获取出售常见问题列表
     * @return Response
     */
    public function sellFaqs(): Response
    {
        $locale = strtolower($this->locale);

        $cacheKey = 'sell_faqs:' . $locale;
        $faqs = Cache::get($cacheKey);

        if (!$faqs) {
            $faqs = SellFaq::getEnabledList($locale);
            // 格式化数据
            $faqs = array_map(function ($item) use ($locale) {
                return [
                    'id' => $item['id'],
                    'question' => $item['question'] ?? '',
                    'answer' => $item['answer'] ?? '',
                ];
            }, $faqs);
            // 缓存 1 小时
            Cache::set($cacheKey, $faqs, 3600);
        }

        return $this->success($faqs);
    }

    /**
     * 获取首页Banner列表
     * @return Response
     */
    public function banners(): Response
    {
        $locale = strtolower($this->locale);
        $position = input('get.position', ''); // 可选：指定位置
        $refresh = input('get.refresh', 0); // 强制刷新缓存

        $cacheKey = 'banners:' . $locale . ':' . ($position ?: 'home');

        // 如果请求强制刷新，删除缓存
        if ($refresh) {
            Cache::delete($cacheKey);
        }

        $banners = Cache::get($cacheKey);

        if (!$banners) {
            if ($position) {
                // 获取指定位置的Banner
                $banners = Banner::getEnabledByPosition($position, $locale);
            } else {
                // 获取所有首页Banner
                $banners = Banner::getHomeBanners($locale);
            }

            // 格式化数据
            $formatBanner = function ($item) {
                return [
                    'id' => $item['id'],
                    'title' => $item['title'] ?? '',
                    'subtitle' => $item['subtitle'] ?? '',
                    'content' => $item['content'] ?? '',
                    'buttonText' => $item['button_text'] ?? '',
                    'image' => $item['image'] ?? '',
                    'displayType' => (int)($item['display_type'] ?? 1),
                    'bgColor' => $item['bg_color'] ?? null,
                    'bgGradient' => $item['bg_gradient'] ?? null,
                    'textColor' => $item['text_color'] ?? '#ffffff',
                    'buttonStyle' => (int)($item['button_style'] ?? 1),
                    'buttonColor' => $item['button_color'] ?? null,
                    'productImages' => $item['product_images'] ?? [],
                    'linkType' => (int)($item['link_type'] ?? 0),
                    'linkValue' => $item['link_value'] ?? '',
                ];
            };

            if ($position) {
                $banners = array_map($formatBanner, $banners);
            } else {
                foreach ($banners as $pos => $list) {
                    $banners[$pos] = array_map($formatBanner, $list);
                }
            }

            // 缓存 30 分钟
            Cache::set($cacheKey, $banners, 1800);
        }

        return $this->success($banners);
    }

    /**
     * 获取应用预览图
     */
    public function appPreviews(): Response
    {
        $list = [];
        for ($i = 1; $i <= 5; $i++) {
            $value = SystemConfig::getConfig('app_preview_' . $i, '');
            if ($value) {
                $value = UrlHelper::getFullUrl($value);
            }
            $list[] = $value;
        }
        return $this->success($list);
    }
}
