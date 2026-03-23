<?php
declare(strict_types=1);

namespace app\common\service;

use app\common\model\SystemConfig;
use think\facade\Log;
use think\facade\Cache;

/**
 * 翻译服务
 * 支持 Google Translate、DeepL、百度翻译
 */
class TranslateService
{
    // 翻译 API 提供商
    const PROVIDER_GOOGLE = 'google';
    const PROVIDER_DEEPL = 'deepl';
    const PROVIDER_BAIDU = 'baidu';

    /** @var string */
    protected $provider;
    /** @var string */
    protected $apiKey;
    /** @var string */
    protected $apiRegion;

    // 语言代码映射（系统代码 -> API 代码）
    /** @var array */
    protected $languageMap = [
        'google' => [
            'zh-tw' => 'zh-TW',
            'zh-cn' => 'zh-CN',
            'en-us' => 'en',
            'ja-jp' => 'ja',
            'ko-kr' => 'ko',
            'fr-fr' => 'fr',
            'de-de' => 'de',
            'es-es' => 'es',
            'it-it' => 'it',
            'pt-pt' => 'pt',
            'ru-ru' => 'ru',
            'th-th' => 'th',
            'vi-vn' => 'vi',
            'id-id' => 'id',
            'ms-my' => 'ms',
            'ar-ae' => 'ar',
        ],
        'deepl' => [
            'zh-tw' => 'ZH',
            'zh-cn' => 'ZH',
            'en-us' => 'EN',
            'ja-jp' => 'JA',
            'ko-kr' => 'KO',
            'fr-fr' => 'FR',
            'de-de' => 'DE',
            'es-es' => 'ES',
            'it-it' => 'IT',
            'pt-pt' => 'PT',
            'ru-ru' => 'RU',
        ],
        'baidu' => [
            'zh-tw' => 'cht',
            'zh-cn' => 'zh',
            'en-us' => 'en',
            'ja-jp' => 'jp',
            'ko-kr' => 'kor',
            'fr-fr' => 'fra',
            'de-de' => 'de',
            'es-es' => 'spa',
            'it-it' => 'it',
            'pt-pt' => 'pt',
            'ru-ru' => 'ru',
            'th-th' => 'th',
            'vi-vn' => 'vie',
        ],
    ];

    public function __construct()
    {
        $this->provider = SystemConfig::getConfig('translate_api_provider', self::PROVIDER_GOOGLE);
        $this->apiKey = SystemConfig::getConfig('translate_api_key', '');
        $this->apiRegion = SystemConfig::getConfig('translate_api_region', '');
    }

    /**
     * 检查配置是否有效
     */
    public function isConfigured(): bool
    {
        return !empty($this->apiKey);
    }

    /**
     * 获取当前提供商
     */
    public function getProvider(): string
    {
        return $this->provider;
    }

    /**
     * 翻译单个文本
     */
    public function translate(string $text, string $from, string $to): string
    {
        if (empty($text) || $from === $to) {
            return $text;
        }

        if (!$this->isConfigured()) {
            Log::warning('TranslateService: API not configured');
            return $text;
        }

        // 缓存键
        $cacheKey = 'translate:' . md5("{$this->provider}:{$from}:{$to}:{$text}");
        $cached = Cache::get($cacheKey);
        if ($cached !== null) {
            return $cached;
        }

        try {
            switch ($this->provider) {
                case self::PROVIDER_GOOGLE:
                    $result = $this->googleTranslate($text, $from, $to);
                    break;
                case self::PROVIDER_DEEPL:
                    $result = $this->deeplTranslate($text, $from, $to);
                    break;
                case self::PROVIDER_BAIDU:
                    $result = $this->baiduTranslate($text, $from, $to);
                    break;
                default:
                    Log::warning("Unknown translation provider: {$this->provider}");
                    $result = $text;
            }

            // 缓存翻译结果（7天）
            Cache::set($cacheKey, $result, 7 * 24 * 3600);

            return $result;
        } catch (\Exception $e) {
            Log::error('TranslateService error: ' . $e->getMessage() . ' | Provider: ' . $this->provider . ' | Text: ' . substr($text, 0, 100));
            // 翻译失败时返回原文，让流程可以继续
            return $text;
        }
    }

    /**
     * 批量翻译（优化版，支持 DeepL 批量 API）
     * @param array $texts 要翻译的文本数组 [key => text, ...]
     * @param string $from 源语言
     * @param string $to 目标语言
     * @return array 翻译结果 [key => translatedText, ...]
     */
    public function batchTranslate(array $texts, string $from, string $to): array
    {
        if (empty($texts) || $from === $to) {
            return $texts;
        }

        if (!$this->isConfigured()) {
            Log::warning('TranslateService: API not configured');
            return $texts;
        }

        // 保护 [XXX] 占位符不被翻译
        $placeholderMaps = [];
        $protectedTexts = [];
        foreach ($texts as $key => $text) {
            [$protected, $map] = $this->protectPlaceholders($text);
            $protectedTexts[$key] = $protected;
            if (!empty($map)) {
                $placeholderMaps[$key] = $map;
            }
        }

        // DeepL 和 Google 支持批量翻译，但需要分片避免单次请求过大
        $maxBatchSize = 50; // 每次 API 请求最多翻译 50 条
        $results = [];

        if ($this->provider === self::PROVIDER_DEEPL || $this->provider === self::PROVIDER_GOOGLE) {
            // 分片批量翻译
            $chunks = array_chunk($protectedTexts, $maxBatchSize, true);
            foreach ($chunks as $chunk) {
                if ($this->provider === self::PROVIDER_DEEPL) {
                    $chunkResults = $this->deeplBatchTranslate($chunk, $from, $to);
                } else {
                    $chunkResults = $this->googleBatchTranslate($chunk, $from, $to);
                }
                $results = array_merge($results, $chunkResults);
            }
        } else {
            // 百度等 API 逐条翻译
            foreach ($protectedTexts as $key => $text) {
                $results[$key] = $this->translate($text, $from, $to);
            }
        }

        // 还原占位符
        foreach ($results as $key => &$text) {
            if (isset($placeholderMaps[$key])) {
                $text = $this->restorePlaceholders($text, $placeholderMaps[$key]);
            }
        }
        unset($text);

        return $results;
    }

    /**
     * 保护文本中的 [XXX] 占位符，替换为不会被翻译的标记
     * @return array [protected_text, placeholder_map]
     */
    protected function protectPlaceholders(string $text): array
    {
        $map = [];
        $index = 0;
        // 匹配 [XXX] 方括号占位符和 {xxx} 花括号占位符
        $protected = preg_replace_callback('/\[[A-Z_]+\]|\{[a-zA-Z_]+\}/', function ($matches) use (&$map, &$index) {
            // 使用 XML 标签作为 token，翻译 API 通常会原样保留 XML 标签
            $token = '<x id="' . $index . '"/>';
            $map[$token] = $matches[0];
            $index++;
            return $token;
        }, $text);

        return [$protected, $map];
    }

    /**
     * 还原被保护的占位符
     */
    protected function restorePlaceholders(string $text, array $map): string
    {
        foreach ($map as $token => $original) {
            $text = str_replace($token, $original, $text);
        }
        // 兜底：清理可能残留的未还原 token（翻译 API 可能轻微修改 token 格式）
        $text = preg_replace_callback('/<x\s+id\s*=\s*"(\d+)"\s*\/?>/i', function ($matches) use ($map) {
            // 尝试用标准格式查找
            $standardToken = '<x id="' . $matches[1] . '"/>';
            return $map[$standardToken] ?? $matches[0];
        }, $text);
        return $text;
    }

    /**
     * DeepL 批量翻译（一次请求翻译多条）
     */
    protected function deeplBatchTranslate(array $texts, string $from, string $to): array
    {
        $fromCode = isset($this->languageMap['deepl'][$from]) ? $this->languageMap['deepl'][$from] : strtoupper(substr($from, 0, 2));
        $toCode = isset($this->languageMap['deepl'][$to]) ? $this->languageMap['deepl'][$to] : strtoupper(substr($to, 0, 2));

        // DeepL Free API 使用不同的域名
        $isFreeApi = strpos($this->apiKey, ':fx') !== false;
        $url = $isFreeApi
            ? 'https://api-free.deepl.com/v2/translate'
            : 'https://api.deepl.com/v2/translate';

        // 准备批量翻译数据
        $keys = array_keys($texts);
        $textValues = array_values($texts);

        // 过滤空值，记录位置
        $validTexts = [];
        $validKeys = [];
        $results = [];

        foreach ($textValues as $i => $text) {
            if (empty($text) || mb_strlen($text) < 2) {
                $results[$keys[$i]] = $text; // 空值直接返回原文
            } else {
                // 检查缓存
                $cacheKey = 'translate:' . md5("{$this->provider}:{$from}:{$to}:{$text}");
                $cached = Cache::get($cacheKey);
                if ($cached !== null) {
                    $results[$keys[$i]] = $cached;
                } else {
                    $validTexts[] = $text;
                    $validKeys[] = $keys[$i];
                }
            }
        }

        if (empty($validTexts)) {
            return $results;
        }

        $params = [
            'text' => $validTexts,
            'source_lang' => $fromCode,
            'target_lang' => $toCode,
        ];

        Log::info("DeepL batch translate: " . count($validTexts) . " texts, from={$from}({$fromCode}) to={$to}({$toCode})");

        try {
            $response = $this->httpPostWithAuth($url, $params, $this->apiKey);
            $data = json_decode($response, true);

            if (isset($data['translations']) && is_array($data['translations'])) {
                foreach ($data['translations'] as $i => $translation) {
                    if (isset($translation['text']) && isset($validKeys[$i])) {
                        $translatedText = $translation['text'];
                        $originalText = $validTexts[$i];
                        $key = $validKeys[$i];

                        // 缓存翻译结果
                        $cacheKey = 'translate:' . md5("{$this->provider}:{$from}:{$to}:{$originalText}");
                        Cache::set($cacheKey, $translatedText, 7 * 24 * 3600);

                        $results[$key] = $translatedText;
                    }
                }
            } else {
                $errorMsg = isset($data['message']) ? $data['message'] : 'Unknown error';
                Log::error("DeepL batch API error: {$errorMsg}");
                // 失败时返回原文
                foreach ($validKeys as $i => $key) {
                    $results[$key] = $validTexts[$i];
                }
            }
        } catch (\Exception $e) {
            Log::error('DeepL batch translate error: ' . $e->getMessage());
            // 失败时返回原文
            foreach ($validKeys as $i => $key) {
                $results[$key] = $validTexts[$i];
            }
        }

        return $results;
    }

    /**
     * Google Translate API
     */
    protected function googleTranslate(string $text, string $from, string $to): string
    {
        $fromCode = $this->languageMap['google'][$from] ?? $from;
        $toCode = $this->languageMap['google'][$to] ?? $to;

        $url = 'https://translation.googleapis.com/language/translate/v2';
        $params = [
            'key' => $this->apiKey,
            'q' => $text,
            'target' => $toCode,
            'format' => 'text',
        ];
        // 'auto' 或空值表示自动检测源语言（省略 source 参数）
        if ($fromCode && $fromCode !== 'auto') {
            $params['source'] = $fromCode;
        }

        $response = $this->httpPost($url, $params);
        $data = json_decode($response, true);

        if (isset($data['data']['translations'][0]['translatedText'])) {
            return $data['data']['translations'][0]['translatedText'];
        }

        throw new \Exception('Google Translate API error: ' . ($data['error']['message'] ?? 'Unknown error'));
    }

    /**
     * Google Translate 批量翻译（一次请求翻译多条）
     * Google v2 API 支持在一个请求中传入多个 q 参数
     */
    protected function googleBatchTranslate(array $texts, string $from, string $to): array
    {
        $fromCode = $this->languageMap['google'][$from] ?? $from;
        $toCode = $this->languageMap['google'][$to] ?? $to;

        $keys = array_keys($texts);
        $textValues = array_values($texts);

        // 过滤空值，检查缓存
        $validTexts = [];
        $validKeys = [];
        $results = [];

        foreach ($textValues as $i => $text) {
            if (empty($text) || mb_strlen($text) < 2) {
                $results[$keys[$i]] = $text;
            } else {
                $cacheKey = 'translate:' . md5("{$this->provider}:{$from}:{$to}:{$text}");
                $cached = Cache::get($cacheKey);
                if ($cached !== null) {
                    $results[$keys[$i]] = $cached;
                } else {
                    $validTexts[] = $text;
                    $validKeys[] = $keys[$i];
                }
            }
        }

        if (empty($validTexts)) {
            return $results;
        }

        // Google v2 API 支持多个 q 参数进行批量翻译
        $url = 'https://translation.googleapis.com/language/translate/v2';

        // 构建请求参数，多个 q 参数
        $params = [
            'key' => $this->apiKey,
            'source' => $fromCode,
            'target' => $toCode,
            'format' => 'text',
        ];

        // 手动构建 query string 以支持多个 q 参数
        $queryParts = [http_build_query($params)];
        foreach ($validTexts as $text) {
            $queryParts[] = 'q=' . urlencode($text);
        }
        $queryString = implode('&', $queryParts);

        Log::info("Google batch translate: " . count($validTexts) . " texts, from={$from}({$fromCode}) to={$to}({$toCode})");

        try {
            $ch = curl_init();
            curl_setopt_array($ch, [
                CURLOPT_URL => $url,
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => $queryString,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => 60,
                CURLOPT_SSL_VERIFYPEER => false,
            ]);

            $response = curl_exec($ch);
            $error = curl_error($ch);
            curl_close($ch);

            if ($error) {
                throw new \Exception('HTTP error: ' . $error);
            }

            $data = json_decode($response, true);

            if (isset($data['data']['translations']) && is_array($data['data']['translations'])) {
                foreach ($data['data']['translations'] as $i => $translation) {
                    if (isset($translation['translatedText']) && isset($validKeys[$i])) {
                        $translatedText = html_entity_decode($translation['translatedText'], ENT_QUOTES, 'UTF-8');
                        $originalText = $validTexts[$i];
                        $key = $validKeys[$i];

                        // 缓存翻译结果
                        $cacheKey = 'translate:' . md5("{$this->provider}:{$from}:{$to}:{$originalText}");
                        Cache::set($cacheKey, $translatedText, 7 * 24 * 3600);

                        $results[$key] = $translatedText;
                    }
                }
            } else {
                $errorMsg = $data['error']['message'] ?? 'Unknown error';
                Log::error("Google batch API error: {$errorMsg}");
                // 失败时返回原文
                foreach ($validKeys as $i => $key) {
                    $results[$key] = $validTexts[$i];
                }
            }
        } catch (\Exception $e) {
            Log::error('Google batch translate error: ' . $e->getMessage());
            // 失败时返回原文
            foreach ($validKeys as $i => $key) {
                $results[$key] = $validTexts[$i];
            }
        }

        return $results;
    }

    /**
     * DeepL API
     * 注意：2025年11月起 DeepL 废弃了表单认证，改用 Header 认证
     */
    protected function deeplTranslate(string $text, string $from, string $to): string
    {
        $fromCode = isset($this->languageMap['deepl'][$from]) ? $this->languageMap['deepl'][$from] : strtoupper(substr($from, 0, 2));
        $toCode = isset($this->languageMap['deepl'][$to]) ? $this->languageMap['deepl'][$to] : strtoupper(substr($to, 0, 2));

        // DeepL Free API 使用不同的域名
        $isFreeApi = strpos($this->apiKey, ':fx') !== false;
        $url = $isFreeApi
            ? 'https://api-free.deepl.com/v2/translate'
            : 'https://api.deepl.com/v2/translate';

        $params = [
            'text' => [$text],
            'target_lang' => $toCode,
        ];
        // 'auto' 或空值表示自动检测源语言
        if ($fromCode && $fromCode !== 'auto' && strtoupper($fromCode) !== 'AUTO') {
            $params['source_lang'] = $fromCode;
        }

        Log::info("DeepL translate request: from={$from}({$fromCode}) to={$to}({$toCode}), url={$url}");

        // 使用新的 Header 认证方式
        $response = $this->httpPostWithAuth($url, $params, $this->apiKey);
        $data = json_decode($response, true);

        if (isset($data['translations'][0]['text'])) {
            return $data['translations'][0]['text'];
        }

        $errorMsg = isset($data['message']) ? $data['message'] : (is_string($response) ? $response : 'Unknown error');
        Log::error("DeepL API error: {$errorMsg}");
        throw new \Exception('DeepL API error: ' . $errorMsg);
    }

    /**
     * 百度翻译 API
     */
    protected function baiduTranslate(string $text, string $from, string $to): string
    {
        $fromCode = $this->languageMap['baidu'][$from] ?? 'auto';
        $toCode = $this->languageMap['baidu'][$to] ?? 'en';

        // API key 格式: appid|secret_key
        [$appId, $secretKey] = explode('|', $this->apiKey . '|');

        $salt = uniqid();
        $sign = md5($appId . $text . $salt . $secretKey);

        $url = 'https://fanyi-api.baidu.com/api/trans/vip/translate';
        $params = [
            'q' => $text,
            'from' => $fromCode,
            'to' => $toCode,
            'appid' => $appId,
            'salt' => $salt,
            'sign' => $sign,
        ];

        $response = $this->httpGet($url, $params);
        $data = json_decode($response, true);

        if (isset($data['trans_result'][0]['dst'])) {
            return $data['trans_result'][0]['dst'];
        }

        throw new \Exception('Baidu Translate API error: ' . ($data['error_msg'] ?? 'Unknown error'));
    }

    /**
     * HTTP POST 请求
     */
    protected function httpPost(string $url, array $data): string
    {
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => http_build_query($data),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_SSL_VERIFYPEER => false,
        ]);

        $response = curl_exec($ch);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            throw new \Exception('HTTP error: ' . $error);
        }

        return $response;
    }

    /**
     * HTTP POST 请求（带 Header 认证，用于 DeepL API）
     */
    protected function httpPostWithAuth(string $url, array $data, string $apiKey): string
    {
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($data),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_HTTPHEADER => [
                'Authorization: DeepL-Auth-Key ' . $apiKey,
                'Content-Type: application/json',
            ],
        ]);

        $response = curl_exec($ch);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            throw new \Exception('HTTP error: ' . $error);
        }

        return $response;
    }

    /**
     * HTTP GET 请求
     */
    protected function httpGet(string $url, array $params): string
    {
        $url .= '?' . http_build_query($params);

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_SSL_VERIFYPEER => false,
        ]);

        $response = curl_exec($ch);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            throw new \Exception('HTTP error: ' . $error);
        }

        return $response;
    }
}
