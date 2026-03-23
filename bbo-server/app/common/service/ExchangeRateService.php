<?php
declare(strict_types=1);

namespace app\common\service;

use app\common\model\SystemConfig;
use app\common\model\Currency;
use think\facade\Log;

/**
 * 汇率更新服务
 * 支持多个汇率 API 提供商
 */
class ExchangeRateService
{
    /**
     * 支持的货币列表（从数据库动态获取）
     * @deprecated 使用 getSupportedCurrencies() 方法代替
     */
    const SUPPORTED_CURRENCIES = ['USD', 'HKD', 'TWD', 'JPY', 'EUR', 'GBP', 'AUD', 'CAD', 'SGD', 'NZD', 'MOP'];

    /**
     * 获取支持的货币列表（从数据库）
     */
    public static function getSupportedCurrencies(): array
    {
        static $currencies = null;
        if ($currencies === null) {
            $currencies = Currency::getActiveCodes();
        }
        return $currencies ?: self::SUPPORTED_CURRENCIES;
    }

    /**
     * API 提供商配置
     */
    const PROVIDERS = [
        'exchangerate-api' => [
            'name' => 'ExchangeRate-API',
            'url' => 'https://v6.exchangerate-api.com/v6/{api_key}/latest/USD',
            'free_url' => 'https://open.er-api.com/v6/latest/USD', // 免费版无需 API Key
            'requires_key' => false, // 免费版不需要
        ],
        'openexchangerates' => [
            'name' => 'Open Exchange Rates',
            'url' => 'https://openexchangerates.org/api/latest.json?app_id={api_key}&base=USD',
            'requires_key' => true,
        ],
        'fixer' => [
            'name' => 'Fixer.io',
            'url' => 'https://data.fixer.io/api/latest?access_key={api_key}&base=USD',
            'requires_key' => true,
        ],
    ];

    /**
     * 更新汇率
     * @return array ['success' => bool, 'message' => string, 'rates' => array]
     */
    public function updateRates(): array
    {
        // 检查是否启用自动更新
        $enabled = SystemConfig::getConfig('exchange_rate_api_enabled');
        if (!$enabled || $enabled === '0') {
            return [
                'success' => false,
                'message' => '自動匯率更新未啟用',
                'rates' => [],
            ];
        }

        $provider = SystemConfig::getConfig('exchange_rate_api_provider', 'exchangerate-api');
        $apiKey = SystemConfig::getConfig('exchange_rate_api_key', '');

        if (!isset(self::PROVIDERS[$provider])) {
            return [
                'success' => false,
                'message' => '不支援的 API 提供商: ' . $provider,
                'rates' => [],
            ];
        }

        $providerConfig = self::PROVIDERS[$provider];

        // 检查是否需要 API Key
        if ($providerConfig['requires_key'] && empty($apiKey)) {
            return [
                'success' => false,
                'message' => $providerConfig['name'] . ' 需要 API 密鑰',
                'rates' => [],
            ];
        }

        try {
            $rates = $this->fetchRates($provider, $apiKey);

            if (empty($rates)) {
                return [
                    'success' => false,
                    'message' => '無法獲取匯率數據',
                    'rates' => [],
                ];
            }

            // 更新数据库中的汇率
            $updatedRates = $this->saveRates($rates);

            // 更新最后更新时间
            SystemConfig::setConfig('exchange_rate_last_update', date('Y-m-d H:i:s'));

            return [
                'success' => true,
                'message' => '匯率更新成功',
                'rates' => $updatedRates,
            ];

        } catch (\Exception $e) {
            Log::error('Exchange rate update failed: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => '匯率更新失敗: ' . $e->getMessage(),
                'rates' => [],
            ];
        }
    }

    /**
     * 从 API 获取汇率
     * @param string $provider
     * @param string $apiKey
     * @return array
     */
    protected function fetchRates(string $provider, string $apiKey): array
    {
        $providerConfig = self::PROVIDERS[$provider];

        // 选择 URL（如果有免费版且没有 API Key，使用免费版）
        if (empty($apiKey) && isset($providerConfig['free_url'])) {
            $url = $providerConfig['free_url'];
        } else {
            $url = str_replace('{api_key}', $apiKey, $providerConfig['url']);
        }

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_HTTPHEADER => [
                'Accept: application/json',
            ],
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($httpCode !== 200 || $response === false) {
            throw new \Exception("API 請求失敗: HTTP $httpCode, Error: $error");
        }

        $data = json_decode($response, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \Exception('無效的 JSON 響應');
        }

        // 解析不同提供商的响应格式
        return $this->parseResponse($provider, $data);
    }

    /**
     * 解析 API 响应
     * @param string $provider
     * @param array $data
     * @return array
     */
    protected function parseResponse(string $provider, array $data): array
    {
        $rates = [];

        switch ($provider) {
            case 'exchangerate-api':
                // ExchangeRate-API 格式: { "rates": { "USD": 1, "HKD": 7.82, ... } }
                if (isset($data['rates'])) {
                    $rates = $data['rates'];
                } elseif (isset($data['conversion_rates'])) {
                    // v6 API 格式
                    $rates = $data['conversion_rates'];
                }
                break;

            case 'openexchangerates':
                // Open Exchange Rates 格式: { "rates": { "USD": 1, "HKD": 7.82, ... } }
                if (isset($data['rates'])) {
                    $rates = $data['rates'];
                }
                break;

            case 'fixer':
                // Fixer.io 格式: { "rates": { "USD": 1, "HKD": 7.82, ... } }
                if (isset($data['rates'])) {
                    $rates = $data['rates'];
                }
                break;
        }

        // 只保留支持的货币（从数据库获取列表）
        $supportedCurrencies = self::getSupportedCurrencies();
        $filteredRates = [];
        foreach ($supportedCurrencies as $currency) {
            if (isset($rates[$currency])) {
                $filteredRates[$currency] = (float)$rates[$currency];
            }
        }

        return $filteredRates;
    }

    /**
     * 保存汇率到数据库
     * @param array $rates
     * @return array
     */
    protected function saveRates(array $rates): array
    {
        $updatedRates = [];

        foreach ($rates as $currency => $rate) {
            $key = 'rate_' . $currency;
            $result = SystemConfig::setConfig($key, (string)$rate);

            if ($result) {
                $updatedRates[$currency] = $rate;
            }
        }

        return $updatedRates;
    }

    /**
     * 获取当前汇率
     * @return array
     */
    public function getCurrentRates(): array
    {
        $rates = [];
        $supportedCurrencies = self::getSupportedCurrencies();

        foreach ($supportedCurrencies as $currency) {
            $rate = SystemConfig::getConfig('rate_' . $currency);
            if ($rate !== null) {
                $rates[$currency] = (float)$rate;
            }
        }

        return $rates;
    }

    /**
     * 获取最后更新时间
     * @return string|null
     */
    public function getLastUpdateTime(): ?string
    {
        return SystemConfig::getConfig('exchange_rate_last_update');
    }

    /**
     * 检查是否启用自动更新
     * @return bool
     */
    public function isAutoUpdateEnabled(): bool
    {
        $enabled = SystemConfig::getConfig('exchange_rate_api_enabled');
        return $enabled && $enabled !== '0';
    }
}
