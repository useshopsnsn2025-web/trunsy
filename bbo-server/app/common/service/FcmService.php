<?php
declare(strict_types=1);

namespace app\common\service;

use app\common\model\SystemConfig;

/**
 * UniPush 2.0 推送服务
 * 通过个推 REST API 发送推送，UniPush 会自动路由到 FCM（海外）
 */
class FcmService
{
    private $appId;
    private $appKey;
    private $masterSecret;
    private $baseUrl = 'https://restapi.getui.com/v2';
    private $authToken = null;
    private $tokenExpiry = 0;

    public function __construct()
    {
        // 从系统配置获取个推参数
        $this->appId = SystemConfig::getConfig('unipush_app_id') ?: '';
        $this->appKey = SystemConfig::getConfig('unipush_app_key') ?: '';
        $this->masterSecret = SystemConfig::getConfig('unipush_master_secret') ?: '';
    }

    /**
     * 发送透传推送（静默唤醒 APP）
     *
     * @param string $cid 用户的 UniPush CID
     * @param array $data 推送数据
     * @return bool 是否发送成功
     */
    public function sendDataMessage(string $cid, array $data = []): bool
    {
        if (empty($cid) || empty($this->appId)) {
            return false;
        }

        $token = $this->getAuthToken();
        if (empty($token)) {
            return false;
        }

        $url = $this->baseUrl . '/' . $this->appId . '/push/single/cid';

        $payload = [
            'request_id' => uniqid('push_', true),
            'audience' => [
                'cid' => [$cid],
            ],
            'push_message' => [
                'transmission' => json_encode(array_merge([
                    'type' => 'keepalive',
                    'timestamp' => time(),
                ], $data)),
            ],
            'push_channel' => [
                'android' => [
                    'ups' => [
                        'transmission' => json_encode(array_merge([
                            'type' => 'keepalive',
                            'timestamp' => time(),
                        ], $data)),
                    ],
                ],
            ],
            'settings' => [
                'strategy' => [
                    'default' => 1, // 优先在线推送
                ],
            ],
        ];

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'token: ' . $token,
            ],
            CURLOPT_POSTFIELDS => json_encode($payload),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 10,
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_SSL_VERIFYPEER => true,
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($httpCode !== 200 || $error) {
            \think\facade\Log::error("UniPush send failed: HTTP {$httpCode}, error: {$error}, response: {$response}");
            return false;
        }

        $result = json_decode($response, true);
        $code = $result['code'] ?? -1;

        if ($code === 0) {
            return true;
        }

        \think\facade\Log::warning("UniPush send failed: code={$code}, msg=" . ($result['msg'] ?? 'unknown'));
        return false;
    }

    /**
     * 获取个推鉴权 token
     */
    private function getAuthToken(): string
    {
        if ($this->authToken && time() < $this->tokenExpiry) {
            return $this->authToken;
        }

        if (empty($this->appKey) || empty($this->masterSecret)) {
            \think\facade\Log::error('UniPush: missing appKey or masterSecret');
            return '';
        }

        $timestamp = (string)(time() * 1000);
        $sign = hash('sha256', $this->appKey . $timestamp . $this->masterSecret);

        $url = $this->baseUrl . '/' . $this->appId . '/auth';

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
            CURLOPT_POSTFIELDS => json_encode([
                'sign' => $sign,
                'timestamp' => $timestamp,
                'appkey' => $this->appKey,
            ]),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 10,
            CURLOPT_SSL_VERIFYPEER => true,
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 200) {
            \think\facade\Log::error("UniPush auth failed: HTTP {$httpCode}, response: {$response}");
            return '';
        }

        $result = json_decode($response, true);
        if (($result['code'] ?? -1) === 0 && !empty($result['data']['token'])) {
            $this->authToken = $result['data']['token'];
            $this->tokenExpiry = time() + 3600; // token 有效期通常是 24 小时，这里保守用 1 小时
            return $this->authToken;
        }

        \think\facade\Log::error("UniPush auth failed: " . $response);
        return '';
    }
}
