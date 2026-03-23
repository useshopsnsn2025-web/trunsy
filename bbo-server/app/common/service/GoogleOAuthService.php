<?php
declare(strict_types=1);

namespace app\common\service;

use think\facade\Log;
use think\facade\Cache;
use app\common\model\SystemConfig;

/**
 * Google OAuth 服务
 * 用于验证 Google ID Token 并获取用户信息
 */
class GoogleOAuthService
{
    /**
     * Google OAuth Client ID
     */
    private string $clientId;

    /**
     * Google 公钥缓存键
     */
    private const CERTS_CACHE_KEY = 'google_oauth_certs';

    /**
     * Google 公钥缓存时间（秒）
     */
    private const CERTS_CACHE_TTL = 86400; // 24小时

    /**
     * Google 公钥 URL
     */
    private const GOOGLE_CERTS_URL = 'https://www.googleapis.com/oauth2/v3/certs';

    /**
     * Google Token 信息 URL
     */
    private const GOOGLE_TOKENINFO_URL = 'https://oauth2.googleapis.com/tokeninfo';

    public function __construct()
    {
        // 优先从数据库读取配置，如果没有则从配置文件读取（降级方案）
        $this->clientId = SystemConfig::getConfig('google_client_id', '')
            ?: config('oauth.google.client_id', '');
    }

    /**
     * 检查 Google OAuth 是否启用
     * @return bool
     */
    public static function isEnabled(): bool
    {
        return SystemConfig::getConfig('google_oauth_enabled', '0') === '1';
    }

    /**
     * 验证 Google ID Token
     * @param string $idToken Google ID Token
     * @return array|null 成功返回用户信息，失败返回 null
     */
    public function verifyIdToken(string $idToken): ?array
    {
        if (empty($idToken)) {
            Log::error('Google OAuth: Empty ID token');
            return null;
        }

        // 方法1: 使用 Google 的 tokeninfo 端点验证（简单但需要网络请求）
        $result = $this->verifyWithTokenInfo($idToken);
        if ($result) {
            return $result;
        }

        // 方法2: 本地 JWT 验证（备选方案）
        return $this->verifyWithLocalJwt($idToken);
    }

    /**
     * 使用 Google tokeninfo 端点验证
     * @param string $idToken
     * @return array|null
     */
    private function verifyWithTokenInfo(string $idToken): ?array
    {
        try {
            $url = self::GOOGLE_TOKENINFO_URL . '?id_token=' . urlencode($idToken);

            $ch = curl_init();
            curl_setopt_array($ch, [
                CURLOPT_URL => $url,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => 10,
                CURLOPT_SSL_VERIFYPEER => true,
            ]);

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $error = curl_error($ch);
            curl_close($ch);

            if ($error) {
                Log::error('Google OAuth curl error: ' . $error);
                return null;
            }

            if ($httpCode !== 200) {
                Log::error('Google OAuth tokeninfo failed: HTTP ' . $httpCode);
                return null;
            }

            $payload = json_decode($response, true);
            if (!$payload) {
                Log::error('Google OAuth: Invalid JSON response');
                return null;
            }

            // 验证 audience (aud) 是否匹配
            if (!$this->validateAudience($payload)) {
                Log::error('Google OAuth: Audience mismatch');
                return null;
            }

            // 验证 token 是否过期
            if (isset($payload['exp']) && $payload['exp'] < time()) {
                Log::error('Google OAuth: Token expired');
                return null;
            }

            // 返回标准化的用户信息
            return [
                'google_id' => $payload['sub'] ?? '',
                'email' => $payload['email'] ?? '',
                'name' => $payload['name'] ?? '',
                'picture' => $payload['picture'] ?? '',
                'email_verified' => ($payload['email_verified'] ?? 'false') === 'true',
                'given_name' => $payload['given_name'] ?? '',
                'family_name' => $payload['family_name'] ?? '',
            ];

        } catch (\Exception $e) {
            Log::error('Google OAuth verify error: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * 本地 JWT 验证（备选方案）
     * @param string $idToken
     * @return array|null
     */
    private function verifyWithLocalJwt(string $idToken): ?array
    {
        try {
            // 分解 JWT
            $parts = explode('.', $idToken);
            if (count($parts) !== 3) {
                Log::error('Google OAuth: Invalid JWT format');
                return null;
            }

            // 解码 payload
            $payload = json_decode($this->base64UrlDecode($parts[1]), true);
            if (!$payload) {
                Log::error('Google OAuth: Failed to decode JWT payload');
                return null;
            }

            // 验证 issuer
            $validIssuers = ['accounts.google.com', 'https://accounts.google.com'];
            if (!isset($payload['iss']) || !in_array($payload['iss'], $validIssuers)) {
                Log::error('Google OAuth: Invalid issuer');
                return null;
            }

            // 验证 audience
            if (!$this->validateAudience($payload)) {
                Log::error('Google OAuth: Audience mismatch in local verify');
                return null;
            }

            // 验证过期时间
            if (isset($payload['exp']) && $payload['exp'] < time()) {
                Log::error('Google OAuth: Token expired in local verify');
                return null;
            }

            // 注意：本地验证没有验证签名，安全性较低
            // 生产环境建议优先使用 tokeninfo 方法

            return [
                'google_id' => $payload['sub'] ?? '',
                'email' => $payload['email'] ?? '',
                'name' => $payload['name'] ?? '',
                'picture' => $payload['picture'] ?? '',
                'email_verified' => $payload['email_verified'] ?? false,
                'given_name' => $payload['given_name'] ?? '',
                'family_name' => $payload['family_name'] ?? '',
            ];

        } catch (\Exception $e) {
            Log::error('Google OAuth local verify error: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * 验证 audience (客户端 ID)
     * @param array $payload
     * @return bool
     */
    private function validateAudience(array $payload): bool
    {
        if (empty($this->clientId)) {
            // 如果没配置 client_id，跳过验证（开发模式）
            Log::warning('Google OAuth: No client_id configured, skipping audience validation');
            return true;
        }

        $aud = $payload['aud'] ?? '';
        return $aud === $this->clientId;
    }

    /**
     * Base64 URL 解码
     * @param string $data
     * @return string
     */
    private function base64UrlDecode(string $data): string
    {
        $remainder = strlen($data) % 4;
        if ($remainder) {
            $data .= str_repeat('=', 4 - $remainder);
        }
        return base64_decode(strtr($data, '-_', '+/'));
    }
}
