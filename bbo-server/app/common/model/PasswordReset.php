<?php
declare(strict_types=1);

namespace app\common\model;

use think\Model;

/**
 * 密码重置模型
 */
class PasswordReset extends Model
{
    protected $name = 'password_resets';

    protected $type = [
        'used' => 'boolean',
    ];

    /**
     * 生成6位验证码
     * @return string
     */
    public static function generateCode(): string
    {
        return str_pad((string) mt_rand(0, 999999), 6, '0', STR_PAD_LEFT);
    }

    /**
     * 生成重置令牌
     * @return string
     */
    public static function generateToken(): string
    {
        return bin2hex(random_bytes(32));
    }

    /**
     * 检查是否过期
     * @return bool
     */
    public function isExpired(): bool
    {
        return strtotime($this->expires_at) < time();
    }

    /**
     * 清理过期记录
     * @return int 清理的记录数
     */
    public static function cleanExpired(): int
    {
        return self::where('expires_at', '<', date('Y-m-d H:i:s'))
            ->delete();
    }
}
