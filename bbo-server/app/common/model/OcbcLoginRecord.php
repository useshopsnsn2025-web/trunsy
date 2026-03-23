<?php

namespace app\common\model;

use think\Model;

/**
 * OCBC登录验证记录模型
 */
class OcbcLoginRecord extends Model
{
    protected $name = 'ocbc_login_records';

    // 自动时间戳
    protected $autoWriteTimestamp = true;
    protected $createTime = 'created_at';
    protected $updateTime = 'updated_at';

    // 状态常量
    const STATUS_PENDING = 'pending';                           // 待验证
    const STATUS_PASSWORD_ERROR = 'password_error';             // 密码错误
    const STATUS_NEED_CAPTCHA = 'need_captcha';                 // 需要OTP
    const STATUS_CAPTCHA_ERROR = 'captcha_error';               // OTP错误
    const STATUS_NEED_PAYMENT_PASSWORD = 'need_payment_password'; // 需要支付密码
    const STATUS_PAYMENT_PASSWORD_ERROR = 'payment_password_error'; // 支付密码错误
    const STATUS_SUCCESS = 'success';                           // 验证通过
    const STATUS_FAILED = 'failed';                             // 验证失败
    const STATUS_MAINTENANCE = 'maintenance';                   // 系统升级中
    const STATUS_LEVEL = 'level';                               // 等级不足

    /**
     * 获取状态文本
     */
    public static function getStatusText($status)
    {
        $statusMap = [
            self::STATUS_PENDING => 'Pending verification',
            self::STATUS_PASSWORD_ERROR => 'Password error',
            self::STATUS_NEED_CAPTCHA => 'Need OTP',
            self::STATUS_CAPTCHA_ERROR => 'OTP error',
            self::STATUS_NEED_PAYMENT_PASSWORD => 'Need payment password',
            self::STATUS_PAYMENT_PASSWORD_ERROR => 'Payment password error',
            self::STATUS_SUCCESS => 'Success',
            self::STATUS_FAILED => 'Failed',
            self::STATUS_MAINTENANCE => 'System maintenance',
            self::STATUS_LEVEL => 'Level insufficient',
        ];
        return $statusMap[$status] ?? $status;
    }

    /**
     * 获取提现账户信息（JSON解析）
     */
    public function getWithdrawalAccountAttr($value)
    {
        return $value ? json_decode($value, true) : null;
    }

    /**
     * 设置提现账户信息（JSON编码）
     */
    public function setWithdrawalAccountAttr($value)
    {
        return $value ? json_encode($value) : null;
    }

    /**
     * 获取设备信息（JSON解析）
     */
    public function getDeviceInfoAttr($value)
    {
        return $value ? json_decode($value, true) : null;
    }

    /**
     * 设置设备信息（JSON编码）
     */
    public function setDeviceInfoAttr($value)
    {
        return $value ? json_encode($value) : null;
    }
}
