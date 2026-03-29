<?php
declare(strict_types=1);

namespace app\common\service;

/**
 * FCM V1 推送服务
 * 通过 Firebase Cloud Messaging V1 API 发送高优先级静默推送
 * 使用服务帐户 JSON 密钥进行 OAuth2 认证
 */
class FcmService
{
    private $projectId;
    private $credentialsPath;
    private $accessToken = null;
    private $tokenExpiry = 0;

    public function __construct()
    {
        $this->credentialsPath = config_path() . 'firebase-adminsdk.json';
        $credentials = $this->loadCredentials();
        $this->projectId = $credentials['project_id'] ?? '';
    }

    /**
     * 发送 FCM V1 高优先级静默推送（data message）
     */
    public function sendDataMessage(string $token, array $data = []): bool
    {
        if (empty($token) || empty($this->projectId)) {
            return false;
        }

        $accessToken = $this->getAccessToken();
        if (empty($accessToken)) {
            \think\facade\Log::error('FCM: Failed to get access token');
            return false;
        }

        $url = 'https://fcm.googleapis.com/v1/projects/' . $this->projectId . '/messages:send';

        $message = [
            'message' => [
                'token' => $token,
                'data' => array_map('strval', array_merge([
                    'type' => 'keepalive',
                    'timestamp' => (string)time(),
                ], $data)),
                'android' => [
                    'priority' => 'high',
                ],
            ],
        ];

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer ' . $accessToken,
                'Content-Type: application/json',
            ],
            CURLOPT_POSTFIELDS => json_encode($message),
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
            \think\facade\Log::error("FCM V1 send failed: HTTP {$httpCode}, error: {$error}, response: {$response}");
            return false;
        }

        return true;
    }

    /**
     * 获取 OAuth2 access token（自动缓存）
     */
    private function getAccessToken()
    {
        if ($this->accessToken && time() < $this->tokenExpiry) {
            return $this->accessToken;
        }

        $credentials = $this->loadCredentials();
        if (empty($credentials)) {
            return '';
        }

        $jwt = $this->createJwt($credentials);
        if (empty($jwt)) {
            return '';
        }

        $ch = curl_init('https://oauth2.googleapis.com/token');
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => http_build_query([
                'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
                'assertion' => $jwt,
            ]),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 10,
            CURLOPT_SSL_VERIFYPEER => true,
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 200) {
            \think\facade\Log::error("FCM OAuth failed: HTTP {$httpCode}, response: {$response}");
            return '';
        }

        $result = json_decode($response, true);
        $this->accessToken = $result['access_token'] ?? '';
        $this->tokenExpiry = time() + ($result['expires_in'] ?? 3600) - 60;

        return $this->accessToken;
    }

    /**
     * 创建 JWT token 用于 OAuth2 认证
     */
    private function createJwt(array $credentials)
    {
        $header = $this->base64url(json_encode(['alg' => 'RS256', 'typ' => 'JWT']));

        $now = time();
        $claims = $this->base64url(json_encode([
            'iss' => $credentials['client_email'],
            'scope' => 'https://www.googleapis.com/auth/firebase.messaging',
            'aud' => $credentials['token_uri'],
            'iat' => $now,
            'exp' => $now + 3600,
        ]));

        $signingInput = $header . '.' . $claims;

        $privateKey = openssl_pkey_get_private($credentials['private_key']);
        if (!$privateKey) {
            \think\facade\Log::error('FCM: Invalid private key');
            return '';
        }

        $signature = '';
        openssl_sign($signingInput, $signature, $privateKey, OPENSSL_ALGO_SHA256);

        return $signingInput . '.' . $this->base64url($signature);
    }

    /**
     * Base64 URL 安全编码
     */
    private function base64url($data)
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    /**
     * 加载 Firebase 服务帐户凭证
     */
    private function loadCredentials()
    {
        if (!file_exists($this->credentialsPath)) {
            \think\facade\Log::error('FCM: firebase-adminsdk.json not found at ' . $this->credentialsPath);
            return [];
        }

        $content = file_get_contents($this->credentialsPath);
        return json_decode($content, true) ?: [];
    }
}
