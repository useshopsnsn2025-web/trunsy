<?php
declare(strict_types=1);

namespace app\common\service;

use think\facade\Db;

/**
 * Google Gemini AI 服务 - 生成商品描述
 * 支持多 API Key 轮换，配额耗尽自动切换
 */
class GeminiService
{
    protected $model = 'gemini-2.0-flash';
    protected $baseUrl = 'https://generativelanguage.googleapis.com/v1beta';

    /**
     * API Key 轮换器
     */
    protected $keyRotator = null;

    public function __construct()
    {
        // 初始化 key 轮换器（冷却1小时）
        $this->keyRotator = new ApiKeyRotator('gemini_api_key', 3600);
    }

    /**
     * 动态获取语言映射（从数据库 languages 表读取，带缓存）
     */
    protected function getLocaleMap(): array
    {
        static $cached = null;
        if ($cached !== null) {
            return $cached;
        }

        $cacheKey = 'ai_locale_map';
        $cached = cache($cacheKey);
        if (!empty($cached)) {
            return $cached;
        }

        $cached = [];
        try {
            $languages = \app\common\model\Language::where('is_active', 1)
                ->field('code, native_name')
                ->select();
            foreach ($languages as $lang) {
                $code = strtolower($lang->code);
                $name = $lang->native_name;
                $cached[$code] = [
                    'name' => $name,
                    'example' => $name . ' description',
                ];
            }
        } catch (\Exception $e) {
            $cached = [
                'en-us' => ['name' => 'English', 'example' => 'English description'],
                'zh-tw' => ['name' => '繁體中文', 'example' => '繁體中文描述'],
                'ja-jp' => ['name' => '日本語', 'example' => '日本語の説明'],
            ];
        }

        if (!empty($cached)) {
            cache($cacheKey, $cached, 3600);
        }

        return $cached;
    }

    /**
     * 根据商品信息生成多语言描述
     * @param array $productInfo 商品信息
     * @param string|null $locale 当前用户语言，优先生成该语言
     */
    public function generateProductDescription(array $productInfo, ?string $locale = null): array
    {
        $locales = $this->getTargetLocales($locale);

        $result = [];
        foreach ($locales as $loc) {
            $result[$loc] = '';
        }

        if (!$this->keyRotator->hasKeys()) {
            return $result;
        }

        $title = $productInfo['title'] ?? '';
        $brand = $productInfo['brand'] ?? '';
        $model = $productInfo['model'] ?? '';
        $storage = $productInfo['storage'] ?? '';
        $color = $productInfo['color'] ?? '';
        $condition = $productInfo['condition_text'] ?? '';
        $price = $productInfo['price'] ?? 0;
        $currency = $productInfo['currency'] ?? 'USD';

        // 随机写作风格，避免千篇一律
        $styles = [
            'Focus on the user experience and daily usage scenarios.',
            'Emphasize the technical specifications and performance advantages.',
            'Write from a value-for-money perspective, highlighting what makes this a great deal.',
            'Use a warm, conversational tone as if recommending to a friend.',
            'Focus on build quality, design aesthetics, and premium feel.',
            'Highlight reliability, durability, and long-term value.',
            'Emphasize portability, convenience, and lifestyle integration.',
            'Write with a professional, authoritative tone focusing on key differentiators.',
        ];
        $openings = [
            'Start with a compelling feature highlight.',
            'Start with a question that addresses a common need.',
            'Start by describing the ideal user for this product.',
            'Start with a bold statement about the product category.',
            'Start with an action-oriented phrase.',
        ];
        $style = $styles[array_rand($styles)];
        $opening = $openings[array_rand($openings)];
        $seed = mt_rand(1000, 9999);

        $jsonExample = $this->buildJsonExample($locales);
        $langInstructions = $this->buildLanguageInstructions($locales);

        $prompt = <<<EOT
You are a creative e-commerce copywriter. Write a UNIQUE product description based on the info below.

Product Info:
- Title: {$title}
- Brand: {$brand}
- Model: {$model}
- Storage: {$storage}
- Color: {$color}
- Condition: {$condition}
- Price: {$currency} {$price}

Writing Style: {$style}
Opening Approach: {$opening}
Variation Seed: {$seed}

Requirements:
1. Write 2-4 sentences per language. Each description MUST be unique — vary sentence structure, word choice, and angle.
2. Do NOT use generic phrases like "Experience the pinnacle of" or "Elevate your". Be specific and natural.
3. Do NOT include the price. Do NOT repeat the full product title verbatim.
4. Mention the color ({$color}) or condition ({$condition}) naturally when relevant.
5. Keep it professional and suitable for an e-commerce listing.
{$langInstructions}

Return ONLY a JSON object (no markdown, no code blocks):
{$jsonExample}
EOT;

        try {
            $response = $this->callApiWithRotation($prompt);
            $parsed = $this->parseJsonResponse($response);
            if ($parsed) {
                $result = array_merge($result, $parsed);
            }
        } catch (\Exception $e) {
            try {
                \think\facade\Log::error('Gemini API error: ' . $e->getMessage());
            } catch (\Exception $ex) {
                // 忽略
            }
        }

        return $result;
    }

    /**
     * 批量生成商品描述
     */
    public function batchGenerateDescriptions(array $items): array
    {
        $results = [];

        foreach ($items as $index => $item) {
            $descriptions = $this->generateProductDescription([
                'title'          => $item['title'] ?? '',
                'brand'          => $item['parsed_attrs']['brand'] ?? '',
                'model'          => $item['parsed_attrs']['model'] ?? '',
                'storage'        => $item['parsed_attrs']['storage'] ?? '',
                'color'          => $item['parsed_attrs']['color'] ?? '',
                'condition_text' => $item['condition_text'] ?? '',
                'price'          => $item['price'] ?? 0,
                'currency'       => $item['currency'] ?? 'USD',
            ]);

            $results[$index] = $descriptions;

            // Gemini Free API 限制：每分钟 15 次
            if ($index < count($items) - 1) {
                usleep(mt_rand(4000000, 5000000)); // 4-5秒间隔
            }
        }

        return $results;
    }

    /**
     * 带 key 轮换的 API 调用
     * 当 key 配额耗尽时自动切换到下一个
     */
    protected function callApiWithRotation(string $prompt): string
    {
        $apiKey = $this->keyRotator->getKey();
        if (!$apiKey) {
            throw new \RuntimeException('No Gemini API key available');
        }

        try {
            return $this->callApi($prompt, $apiKey);
        } catch (\RuntimeException $e) {
            // 429 (rate limit) 或 403 (quota exceeded) 时切换 key
            if (strpos($e->getMessage(), 'HTTP 429') !== false
                || strpos($e->getMessage(), 'HTTP 403') !== false
                || strpos($e->getMessage(), 'RESOURCE_EXHAUSTED') !== false) {
                $this->keyRotator->markFailed($apiKey);

                // 尝试下一个 key
                $nextKey = $this->keyRotator->getKey();
                if ($nextKey && $nextKey !== $apiKey) {
                    return $this->callApi($prompt, $nextKey);
                }
            }
            throw $e;
        }
    }

    /**
     * 调用 Gemini API
     */
    protected function callApi(string $prompt, string $apiKey): string
    {
        $url = "{$this->baseUrl}/models/{$this->model}:generateContent?key={$apiKey}";

        $payload = [
            'contents' => [
                [
                    'parts' => [
                        ['text' => $prompt],
                    ],
                ],
            ],
            'generationConfig' => [
                'temperature' => 0.7,
                'maxOutputTokens' => 500,
            ],
        ];

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL            => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => json_encode($payload),
            CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
            CURLOPT_TIMEOUT        => 30,
            CURLOPT_SSL_VERIFYPEER => false,
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($response === false) {
            throw new \RuntimeException("cURL error: {$error}");
        }

        if ($httpCode !== 200) {
            throw new \RuntimeException("Gemini API HTTP {$httpCode}: {$response}");
        }

        return $response;
    }

    /**
     * 解析 Gemini 响应中的 JSON
     */
    protected function parseJsonResponse(string $response): ?array
    {
        $data = json_decode($response, true);
        if (!$data) {
            return null;
        }

        $text = $data['candidates'][0]['content']['parts'][0]['text'] ?? '';
        if (empty($text)) {
            return null;
        }

        // 清理可能的 markdown 代码块
        $text = preg_replace('/```json\s*/', '', $text);
        $text = preg_replace('/```\s*/', '', $text);
        $text = trim($text);

        $parsed = json_decode($text, true);
        if (!$parsed || !is_array($parsed)) {
            return null;
        }

        // 动态提取所有 locale key
        $result = [];
        foreach ($parsed as $key => $value) {
            if (is_string($value) && preg_match('/^[a-z]{2}-[a-z]{2}$/', $key)) {
                $result[$key] = $value;
            }
        }

        return !empty($result) ? $result : null;
    }

    /**
     * 确定需要生成的语言列表
     */
    protected function getTargetLocales(?string $currentLocale): array
    {
        $baseLocales = ['en-us', 'zh-tw', 'ja-jp'];

        if ($currentLocale) {
            $currentLocale = strtolower($currentLocale);
            if (!in_array($currentLocale, $baseLocales) && isset($this->getLocaleMap()[$currentLocale])) {
                $baseLocales[] = $currentLocale;
            }
        }

        return $baseLocales;
    }

    /**
     * 构建 JSON 示例字符串
     */
    protected function buildJsonExample(array $locales): string
    {
        $parts = [];
        foreach ($locales as $locale) {
            $example = $this->getLocaleMap()[$locale]['example'] ?? 'Description';
            $parts[] = '"' . $locale . '": "' . $example . '"';
        }
        return '{' . implode(', ', $parts) . '}';
    }

    /**
     * 构建语言特定指令
     */
    protected function buildLanguageInstructions(array $locales): string
    {
        $instructions = [];
        foreach ($locales as $locale) {
            if (isset($this->getLocaleMap()[$locale])) {
                $langName = $this->getLocaleMap()[$locale]['name'];
                $instructions[] = "{$locale}: Write in {$langName}";
            }
        }
        if (empty($instructions)) {
            return '';
        }
        return "6. Language requirements:\n   - " . implode("\n   - ", $instructions);
    }

    /**
     * 检查 API Key 是否配置
     */
    public function isConfigured(): bool
    {
        return $this->keyRotator->hasKeys();
    }
}
