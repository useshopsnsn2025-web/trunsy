<?php
declare(strict_types=1);

namespace app\common\service;

use think\facade\Config;
use think\facade\Filesystem;
use app\common\model\SystemConfig;

/**
 * Cloudflare R2 存储服务
 * 兼容本地存储和 R2 云存储
 */
class R2Storage
{
    /**
     * R2 配置
     * @var array
     */
    protected $config;

    /**
     * 是否启用 R2
     * @var bool
     */
    protected $enabled;

    /**
     * 构造函数
     */
    public function __construct()
    {
        $this->loadConfig();
    }

    /**
     * 加载配置
     * 优先从数据库读取，降级到 config/r2.php
     */
    protected function loadConfig(): void
    {
        // 从数据库获取配置
        $dbEnabled = SystemConfig::getConfig('r2_enabled');

        if ($dbEnabled !== null) {
            // 使用数据库配置
            $this->config = [
                'enabled' => in_array(strtolower((string)$dbEnabled), ['true', '1', 'yes']),
                'account_id' => SystemConfig::getConfig('r2_account_id', ''),
                'access_key_id' => SystemConfig::getConfig('r2_access_key_id', ''),
                'secret_access_key' => SystemConfig::getConfig('r2_secret_access_key', ''),
                'bucket' => SystemConfig::getConfig('r2_bucket', 'bbo-assets'),
                'endpoint' => SystemConfig::getConfig('r2_endpoint', ''),
                'public_url' => SystemConfig::getConfig('r2_public_url', ''),
                'region' => 'auto',
                'upload' => Config::get('r2.upload', [
                    'max_image_size' => 5 * 1024 * 1024,
                    'max_video_size' => 50 * 1024 * 1024,
                    'allowed_image_types' => ['image/jpeg', 'image/png', 'image/gif', 'image/webp'],
                    'allowed_video_types' => ['video/mp4', 'video/quicktime', 'video/x-msvideo'],
                ]),
            ];
        } else {
            // 降级到文件配置
            $this->config = Config::get('r2');
        }

        $this->enabled = $this->config['enabled'] ?? false;
    }

    /**
     * 上传图片
     * @param mixed $file 上传的文件 (think\file\UploadedFile)
     * @param string $type 上传类型 (goods, avatar, chat, editor 等)
     * @return array ['url' => string, 'path' => string]
     * @throws \Exception
     */
    public function uploadImage($file, string $type = 'common'): array
    {
        // 验证文件类型
        $mimeType = $file->getOriginalMime();
        $allowedTypes = $this->config['upload']['allowed_image_types'] ?? ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        if (!in_array($mimeType, $allowedTypes)) {
            throw new \Exception('Invalid image type. Allowed: jpg, png, gif, webp');
        }

        // 验证文件大小
        $maxSize = $this->config['upload']['max_image_size'] ?? 5 * 1024 * 1024;
        if ($file->getSize() > $maxSize) {
            throw new \Exception('Image size exceeds ' . ($maxSize / 1024 / 1024) . 'MB limit');
        }

        if ($this->enabled) {
            return $this->uploadToR2($file, $type, 'image');
        }

        return $this->uploadToLocal($file, $type, 'image');
    }

    /**
     * 上传视频
     * @param mixed $file 上传的文件 (think\file\UploadedFile)
     * @param string $type 上传类型
     * @return array ['url' => string, 'path' => string]
     * @throws \Exception
     */
    public function uploadFile($file, string $type = 'common'): array
    {
        $maxSize = 200 * 1024 * 1024; // 200MB
        if ($file->getSize() > $maxSize) {
            throw new \Exception('File size exceeds ' . ($maxSize / 1024 / 1024) . 'MB limit');
        }

        if ($this->enabled) {
            return $this->uploadToR2($file, $type, 'file');
        }

        return $this->uploadToLocal($file, $type, 'file');
    }

    public function uploadVideo($file, string $type = 'common'): array
    {
        // 验证文件类型
        $mimeType = $file->getOriginalMime();
        $allowedTypes = $this->config['upload']['allowed_video_types'] ?? ['video/mp4', 'video/quicktime', 'video/x-msvideo'];
        if (!in_array($mimeType, $allowedTypes)) {
            throw new \Exception('Invalid video type. Allowed: mp4, mov, avi');
        }

        // 验证文件大小
        $maxSize = $this->config['upload']['max_video_size'] ?? 50 * 1024 * 1024;
        if ($file->getSize() > $maxSize) {
            throw new \Exception('Video size exceeds ' . ($maxSize / 1024 / 1024) . 'MB limit');
        }

        if ($this->enabled) {
            return $this->uploadToR2($file, $type, 'video');
        }

        return $this->uploadToLocal($file, $type, 'video');
    }

    /**
     * 上传到 Cloudflare R2
     * @param mixed $file 文件
     * @param string $type 类型
     * @param string $fileType 文件类型
     * @return array
     * @throws \Exception
     */
    protected function uploadToR2($file, string $type, string $fileType): array
    {
        $publicUrl = $this->config['public_url'];
        $workerUrl = SystemConfig::getConfig('r2_worker_url', '');
        $workerSecret = SystemConfig::getConfig('r2_worker_secret', '');

        // 生成文件路径
        $date = date('Ymd');
        $extension = $file->getOriginalExtension() ?: $this->getExtensionFromMime($file->getOriginalMime());
        $filename = md5(uniqid((string)mt_rand(), true)) . '.' . $extension;
        $key = "{$type}/{$date}/{$filename}";

        // 读取文件内容
        $fileContent = file_get_contents($file->getRealPath());
        $contentType = $file->getOriginalMime();

        // 优先使用 Worker 代理上传（解决 S3 端点 SSL 兼容性问题）
        if (!empty($workerUrl) && !empty($workerSecret)) {
            $result = $this->uploadViaWorker($workerUrl, $workerSecret, $key, $fileContent, $contentType);
        } else {
            // 回退到 S3 签名直传
            $endpoint = $this->config['endpoint'];
            $bucket = $this->config['bucket'];
            $accessKeyId = $this->config['access_key_id'];
            $secretAccessKey = $this->config['secret_access_key'];
            $result = $this->putObjectToR2($endpoint, $bucket, $key, $fileContent, $contentType, $accessKeyId, $secretAccessKey);
        }

        if (!$result) {
            throw new \Exception('Failed to upload to R2: ' . $this->lastR2Error);
        }

        // 返回公开访问 URL
        $url = rtrim($publicUrl, '/') . '/' . $key;

        return [
            'url' => $url,
            'path' => $key,
        ];
    }

    /**
     * 最后一次R2错误信息
     * @var string
     */
    protected $lastR2Error = '';

    /**
     * 使用 AWS Signature V4 上传到 R2
     * @param string $endpoint R2 端点
     * @param string $bucket 存储桶
     * @param string $key 对象键
     * @param string $content 文件内容
     * @param string $contentType 内容类型
     * @param string $accessKeyId Access Key
     * @param string $secretAccessKey Secret Key
     * @return bool
     */
    protected function putObjectToR2(
        string $endpoint,
        string $bucket,
        string $key,
        string $content,
        string $contentType,
        string $accessKeyId,
        string $secretAccessKey
    ): bool {
        $host = parse_url($endpoint, PHP_URL_HOST);
        $url = "{$endpoint}/{$bucket}/{$key}";

        $date = gmdate('Ymd\THis\Z');
        $dateShort = gmdate('Ymd');
        $region = 'auto';
        $service = 's3';

        $contentHash = hash('sha256', $content);

        // 构建规范请求
        $canonicalHeaders = "content-type:{$contentType}\nhost:{$host}\nx-amz-content-sha256:{$contentHash}\nx-amz-date:{$date}\n";
        $signedHeaders = 'content-type;host;x-amz-content-sha256;x-amz-date';

        $canonicalRequest = "PUT\n/{$bucket}/{$key}\n\n{$canonicalHeaders}\n{$signedHeaders}\n{$contentHash}";

        // 构建待签名字符串
        $credentialScope = "{$dateShort}/{$region}/{$service}/aws4_request";
        $stringToSign = "AWS4-HMAC-SHA256\n{$date}\n{$credentialScope}\n" . hash('sha256', $canonicalRequest);

        // 计算签名
        $kDate = hash_hmac('sha256', $dateShort, "AWS4{$secretAccessKey}", true);
        $kRegion = hash_hmac('sha256', $region, $kDate, true);
        $kService = hash_hmac('sha256', $service, $kRegion, true);
        $kSigning = hash_hmac('sha256', 'aws4_request', $kService, true);
        $signature = hash_hmac('sha256', $stringToSign, $kSigning);

        // 构建 Authorization 头
        $authorization = "AWS4-HMAC-SHA256 Credential={$accessKeyId}/{$credentialScope}, SignedHeaders={$signedHeaders}, Signature={$signature}";

        // 发送请求
        $headers = [
            "Content-Type: {$contentType}",
            "Host: {$host}",
            "x-amz-content-sha256: {$contentHash}",
            "x-amz-date: {$date}",
            "Authorization: {$authorization}",
        ];

        // 尝试多种 SSL 配置
        $sslConfigs = [
            // 配置1: 使用 TLS 1.2 + 特定密码套件
            [
                CURLOPT_SSLVERSION => CURL_SSLVERSION_TLSv1_2,
                CURLOPT_SSL_CIPHER_LIST => 'ECDHE-RSA-AES128-GCM-SHA256:ECDHE-RSA-AES256-GCM-SHA384:ECDHE-RSA-AES128-SHA256:ECDHE-RSA-AES256-SHA384',
            ],
            // 配置2: 仅 TLS 1.2
            [
                CURLOPT_SSLVERSION => CURL_SSLVERSION_TLSv1_2,
            ],
            // 配置3: TLS 1.0+
            [
                CURLOPT_SSLVERSION => CURL_SSLVERSION_TLSv1,
            ],
            // 配置4: 默认
            [],
        ];

        $response = false;
        $httpCode = 0;
        $curlError = '';

        foreach ($sslConfigs as $sslConfig) {
            $ch = curl_init();
            $options = [
                CURLOPT_URL => $url,
                CURLOPT_CUSTOMREQUEST => 'PUT',
                CURLOPT_POSTFIELDS => $content,
                CURLOPT_HTTPHEADER => $headers,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => 120,
                CURLOPT_CONNECTTIMEOUT => 30,
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_SSL_VERIFYHOST => 0,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_FOLLOWLOCATION => true,
            ];

            // 合并 SSL 配置
            foreach ($sslConfig as $key => $value) {
                $options[$key] = $value;
            }

            curl_setopt_array($ch, $options);

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curlError = curl_error($ch);
            $curlErrno = curl_errno($ch);
            curl_close($ch);

            // 如果成功或者不是 SSL 错误，跳出循环
            if ($curlErrno === 0 || ($curlErrno !== 35 && strpos($curlError, 'SSL') === false && strpos($curlError, 'handshake') === false)) {
                break;
            }
        }

        if ($httpCode < 200 || $httpCode >= 300) {
            $this->lastR2Error = "HTTP {$httpCode}: {$response} (curl: {$curlError})";
        }

        return $httpCode >= 200 && $httpCode < 300;
    }

    /**
     * 上传到本地存储
     * @param mixed $file 文件
     * @param string $type 类型
     * @param string $fileType 文件类型
     * @return array
     * @throws \Exception
     */
    protected function uploadToLocal($file, string $type, string $fileType): array
    {
        $date = date('Ymd');
        $savePath = "{$type}/{$date}";

        $saveName = Filesystem::disk('public')->putFile($savePath, $file);

        if (!$saveName) {
            throw new \Exception('Failed to save file');
        }

        // 本地存储返回相对路径，由 UrlHelper::getFullUrl() 在 API 输出时动态拼接域名
        $relativePath = '/storage/' . str_replace('\\', '/', $saveName);

        return [
            'url' => $relativePath,
            'path' => $saveName,
        ];
    }

    /**
     * 根据 MIME 类型获取扩展名
     * @param string $mimeType
     * @return string
     */
    protected function getExtensionFromMime(string $mimeType): string
    {
        $map = [
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/gif' => 'gif',
            'image/webp' => 'webp',
            'video/mp4' => 'mp4',
            'video/quicktime' => 'mov',
            'video/x-msvideo' => 'avi',
        ];

        return $map[$mimeType] ?? 'bin';
    }

    /**
     * 删除文件
     * @param string $path 文件路径
     * @return bool
     */
    public function delete(string $path): bool
    {
        if ($this->enabled) {
            return $this->deleteFromR2($path);
        }

        return $this->deleteFromLocal($path);
    }

    /**
     * 从 R2 删除文件
     * @param string $key 对象键
     * @return bool
     */
    protected function deleteFromR2(string $key): bool
    {
        $endpoint = $this->config['endpoint'];
        $bucket = $this->config['bucket'];
        $accessKeyId = $this->config['access_key_id'];
        $secretAccessKey = $this->config['secret_access_key'];

        $host = parse_url($endpoint, PHP_URL_HOST);
        $url = "{$endpoint}/{$bucket}/{$key}";

        $date = gmdate('Ymd\THis\Z');
        $dateShort = gmdate('Ymd');
        $region = 'auto';
        $service = 's3';

        $contentHash = hash('sha256', '');

        $canonicalHeaders = "host:{$host}\nx-amz-content-sha256:{$contentHash}\nx-amz-date:{$date}\n";
        $signedHeaders = 'host;x-amz-content-sha256;x-amz-date';

        $canonicalRequest = "DELETE\n/{$bucket}/{$key}\n\n{$canonicalHeaders}\n{$signedHeaders}\n{$contentHash}";

        $credentialScope = "{$dateShort}/{$region}/{$service}/aws4_request";
        $stringToSign = "AWS4-HMAC-SHA256\n{$date}\n{$credentialScope}\n" . hash('sha256', $canonicalRequest);

        $kDate = hash_hmac('sha256', $dateShort, "AWS4{$secretAccessKey}", true);
        $kRegion = hash_hmac('sha256', $region, $kDate, true);
        $kService = hash_hmac('sha256', $service, $kRegion, true);
        $kSigning = hash_hmac('sha256', 'aws4_request', $kService, true);
        $signature = hash_hmac('sha256', $stringToSign, $kSigning);

        $authorization = "AWS4-HMAC-SHA256 Credential={$accessKeyId}/{$credentialScope}, SignedHeaders={$signedHeaders}, Signature={$signature}";

        $headers = [
            "Host: {$host}",
            "x-amz-content-sha256: {$contentHash}",
            "x-amz-date: {$date}",
            "Authorization: {$authorization}",
        ];

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_CUSTOMREQUEST => 'DELETE',
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => 0,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        return $httpCode >= 200 && $httpCode < 300;
    }

    /**
     * 从本地删除文件
     * @param string $path 文件路径
     * @return bool
     */
    protected function deleteFromLocal(string $path): bool
    {
        try {
            return Filesystem::disk('public')->delete($path);
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * 通过 Cloudflare Worker 代理上传到 R2
     */
    protected function uploadViaWorker(string $workerUrl, string $secret, string $key, string $content, string $contentType): bool
    {
        $url = rtrim($workerUrl, '/') . '/' . $key;

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_CUSTOMREQUEST => 'PUT',
            CURLOPT_POSTFIELDS => $content,
            CURLOPT_HTTPHEADER => [
                'X-Upload-Key: ' . $secret,
                'Content-Type: ' . $contentType,
                'Content-Length: ' . strlen($content),
            ],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 120,
            CURLOPT_CONNECTTIMEOUT => 30,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => 0,
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($httpCode === 200) {
            return true;
        }

        $this->lastR2Error = "Worker HTTP {$httpCode}: {$response} (curl: {$curlError})";
        return false;
    }

    /**
     * 通过 Cloudflare Worker 代理删除 R2 文件
     */
    protected function deleteViaWorker(string $workerUrl, string $secret, string $key): bool
    {
        $url = rtrim($workerUrl, '/') . '/' . $key;

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_CUSTOMREQUEST => 'DELETE',
            CURLOPT_HTTPHEADER => [
                'X-Upload-Key: ' . $secret,
            ],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => 0,
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        return $httpCode === 200;
    }
}
