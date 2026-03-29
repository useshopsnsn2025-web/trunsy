<?php
declare(strict_types=1);

namespace app\common\service;

use app\common\model\SystemConfig;

class FcmService
{
    /**
     * 发送 FCM 高优先级静默推送（data message）
     *
     * @param string $token 用户的 FCM token
     * @param array $data 推送数据
     * @return bool 是否发送成功
     */
    public function sendDataMessage(string $token, array $data = []): bool
    {
        $serverKey = SystemConfig::getConfig('fcm_server_key');
        if (empty($serverKey) || empty($token)) {
            return false;
        }

        $payload = [
            'to' => $token,
            'priority' => 'high',
            'data' => array_merge([
                'type' => 'keepalive',
                'timestamp' => time(),
            ], $data),
        ];

        $ch = curl_init('https://fcm.googleapis.com/fcm/send');
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => [
                'Authorization: key=' . $serverKey,
                'Content-Type: application/json',
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
            \think\facade\Log::error("FCM send failed: HTTP {$httpCode}, error: {$error}, response: {$response}");
            return false;
        }

        $result = json_decode($response, true);
        if (($result['success'] ?? 0) > 0) {
            return true;
        }

        \think\facade\Log::warning("FCM send no success: " . $response);
        return false;
    }
}
