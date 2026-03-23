<?php
declare(strict_types=1);

namespace app\common\service;

use app\common\model\UserCoupon;
use app\common\model\SystemConfig;
use think\facade\Log;
use think\facade\Db;

/**
 * 新用户优惠券服务
 * 负责为新注册用户发放欢迎优惠券
 */
class NewUserCouponService
{
    /**
     * 优惠券来源标识
     */
    const SOURCE_NEW_USER_WELCOME = 'new_user_welcome';

    /**
     * 为新用户发放欢迎优惠券
     * @param int $userId 用户ID
     * @param string $locale 用户语言偏好
     * @return array|null 优惠券信息，失败返回null
     */
    public function grantWelcomeCoupon(int $userId, string $locale = 'en-us'): ?array
    {
        // 检查是否启用新人优惠券
        $enabled = SystemConfig::getConfig('new_user_coupon_enabled', '0');
        if (!$enabled || $enabled === '0' || $enabled === 'false') {
            Log::info('New user coupon disabled, skip granting', ['user_id' => $userId]);
            return null;
        }

        // 检查用户是否已领取过新人优惠券
        $existingCoupon = UserCoupon::where('user_id', $userId)
            ->where('source', self::SOURCE_NEW_USER_WELCOME)
            ->find();
        if ($existingCoupon) {
            Log::info('User already has welcome coupon', ['user_id' => $userId]);
            return null;
        }

        // 获取优惠券配置
        $amount = (float) SystemConfig::getConfig('new_user_coupon_amount', '5');
        $minAmount = (float) SystemConfig::getConfig('new_user_coupon_min_amount', '50');
        $validDays = (int) SystemConfig::getConfig('new_user_coupon_valid_days', '30');

        Db::startTrans();
        try {
            // 生成唯一优惠码
            $couponCode = $this->generateCouponCode();
            $expireDate = date('Y-m-d H:i:s', strtotime("+{$validDays} days"));

            // 创建用户优惠券
            $userCoupon = new UserCoupon();
            $userCoupon->user_id = $userId;
            $userCoupon->coupon_id = 0; // 系统优惠券，无关联活动
            $userCoupon->code = $couponCode;
            $userCoupon->type = 'fixed'; // 固定金额减免
            $userCoupon->amount = $amount;
            $userCoupon->min_amount = $minAmount;
            $userCoupon->currency = 'USD';
            $userCoupon->expired_at = $expireDate;
            $userCoupon->source = self::SOURCE_NEW_USER_WELCOME;
            $userCoupon->status = UserCoupon::STATUS_UNUSED;
            $userCoupon->received_at = date('Y-m-d H:i:s'); // 领取时间（必填字段）
            $userCoupon->created_at = date('Y-m-d H:i:s');
            $userCoupon->save();

            Db::commit();

            Log::info('Welcome coupon granted successfully', [
                'user_id' => $userId,
                'coupon_code' => $couponCode,
                'amount' => $amount,
                'expire_date' => $expireDate,
            ]);

            // 返回优惠券信息（用于邮件模板变量）
            return [
                'id' => $userCoupon->id,
                'code' => $couponCode,
                'amount' => $amount,
                'min_amount' => $minAmount,
                'currency' => 'USD',
                'expire_at' => $expireDate,
                'valid_days' => $validDays,
                // 格式化后的显示值
                'formatted_amount' => '$' . number_format($amount, 2),
                'formatted_min_amount' => '$' . number_format($minAmount, 2),
                'formatted_expire_date' => $this->formatExpireDate($expireDate, $locale),
            ];

        } catch (\Exception $e) {
            Db::rollback();
            Log::error('Failed to grant welcome coupon', [
                'user_id' => $userId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return null;
        }
    }

    /**
     * 生成唯一优惠码
     * 格式：NEW + 8位大写字母数字
     * @return string
     */
    protected function generateCouponCode(): string
    {
        $prefix = 'NEW';
        $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        $code = '';
        for ($i = 0; $i < 8; $i++) {
            $code .= $chars[random_int(0, strlen($chars) - 1)];
        }
        return $prefix . $code;
    }

    /**
     * 格式化过期日期（根据语言）
     * @param string $date
     * @param string $locale
     * @return string
     */
    protected function formatExpireDate(string $date, string $locale): string
    {
        $timestamp = strtotime($date);

        switch ($locale) {
            case 'zh-tw':
                return date('Y年m月d日', $timestamp);
            case 'ja-jp':
                return date('Y年m月d日', $timestamp);
            default:
                return date('F j, Y', $timestamp);
        }
    }
}
