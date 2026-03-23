<?php
declare(strict_types=1);

namespace app\api\controller;

use think\Response;
use think\facade\Db;
use app\common\model\Coupon as CouponModel;
use app\common\model\UserCoupon;

/**
 * 优惠券控制器
 */
class Coupon extends Base
{
    /**
     * 获取用户优惠券列表
     * @return Response
     */
    public function list(): Response
    {
        $userId = $this->getUserId();
        if (!$userId) {
            return $this->error('请先登录', 401);
        }

        [$page, $pageSize] = $this->getPageParams();
        $status = input('status', 'available'); // available, used, expired

        // 使用 LEFT JOIN 支持系统优惠券（coupon_id = 0）
        $query = UserCoupon::alias('uc')
            ->leftJoin('coupons c', 'uc.coupon_id = c.id')
            ->where('uc.user_id', $userId);

        $now = date('Y-m-d H:i:s');

        // 根据状态筛选
        switch ($status) {
            case 'available':
                $query->where('uc.status', UserCoupon::STATUS_UNUSED)
                      ->where('uc.expired_at', '>', $now);
                break;
            case 'used':
                $query->where('uc.status', UserCoupon::STATUS_USED);
                break;
            case 'expired':
                $query->where(function ($q) use ($now) {
                    $q->where('uc.status', UserCoupon::STATUS_EXPIRED)
                      ->whereOr(function ($q2) use ($now) {
                          $q2->where('uc.status', UserCoupon::STATUS_UNUSED)
                             ->where('uc.expired_at', '<=', $now);
                      });
                });
                break;
        }

        $total = $query->count();

        $list = $query->field([
                'uc.id',
                'uc.coupon_id',
                'uc.code',
                'uc.type as uc_type',
                'uc.amount as uc_amount',
                'uc.min_amount as uc_min_amount',
                'uc.currency as uc_currency',
                'uc.source',
                'uc.status',
                'uc.received_at',
                'uc.used_at',
                'uc.expired_at',
                'c.name',
                'c.type',
                'c.value',
                'c.min_amount',
                'c.max_discount',
                'c.scope',
                'c.scope_ids',
            ])
            ->order('uc.id', 'desc')
            ->page($page, $pageSize)
            ->select();

        // 转换数据格式
        $items = [];
        foreach ($list as $item) {
            // 判断是系统优惠券还是活动优惠券
            $isSystemCoupon = empty($item['coupon_id']) || $item['coupon_id'] == 0;

            if ($isSystemCoupon) {
                // 系统优惠券（如新人优惠券）
                $name = $this->getSystemCouponName($item['source'], $this->locale);
                $description = $this->getSystemCouponDescription($item['source'], $this->locale);
                $type = $item['uc_type'] === 'fixed' ? CouponModel::TYPE_FIXED : CouponModel::TYPE_DISCOUNT;
                $value = (float) $item['uc_amount'];
                $minAmount = (float) $item['uc_min_amount'];
                $maxDiscount = null;
                $scope = 0; // 全场通用
                $scopeIds = null;
            } else {
                // 活动优惠券
                $coupon = CouponModel::find($item['coupon_id']);
                $name = $coupon ? $coupon->getTranslated('name', $this->locale) : $item['name'];
                $description = $coupon ? $coupon->getTranslated('description', $this->locale) : '';
                $type = (int) $item['type'];
                $value = (float) $item['value'];
                $minAmount = (float) $item['min_amount'];
                $maxDiscount = $item['max_discount'] ? (float) $item['max_discount'] : null;
                $scope = (int) $item['scope'];
                $scopeIds = $item['scope_ids'];
            }

            // 判断实际状态
            $actualStatus = $item['status'];
            if ($item['status'] === UserCoupon::STATUS_UNUSED && strtotime($item['expired_at']) < time()) {
                $actualStatus = UserCoupon::STATUS_EXPIRED;
            }

            $items[] = [
                'id' => (int) $item['id'],
                'couponId' => (int) $item['coupon_id'],
                'code' => $item['code'],
                'name' => $name,
                'description' => $description,
                'type' => $type,
                'value' => $value,
                'minAmount' => $minAmount,
                'maxDiscount' => $maxDiscount,
                'scope' => $scope,
                'scopeIds' => $scopeIds,
                'source' => $item['source'],
                'isSystemCoupon' => $isSystemCoupon,
                'status' => $actualStatus,
                'receivedAt' => $item['received_at'],
                'usedAt' => $item['used_at'],
                'expiredAt' => $item['expired_at'],
            ];
        }

        return $this->paginate($items, $total, $page, $pageSize);
    }

    /**
     * 获取系统优惠券名称
     */
    private function getSystemCouponName(string $source, string $locale): string
    {
        $names = [
            'new_user_welcome' => [
                'en-us' => 'New Member Welcome Coupon',
                'zh-tw' => '新會員歡迎優惠券',
                'ja-jp' => '新規会員ウェルカムクーポン',
            ],
        ];

        return $names[$source][$locale] ?? $names[$source]['en-us'] ?? 'System Coupon';
    }

    /**
     * 获取系统优惠券描述
     */
    private function getSystemCouponDescription(string $source, string $locale): string
    {
        $descriptions = [
            'new_user_welcome' => [
                'en-us' => 'Exclusive welcome gift for new members',
                'zh-tw' => '新會員專屬歡迎禮',
                'ja-jp' => '新規会員限定ウェルカムギフト',
            ],
        ];

        return $descriptions[$source][$locale] ?? $descriptions[$source]['en-us'] ?? '';
    }

    /**
     * 获取可用优惠券数量
     * @return Response
     */
    public function count(): Response
    {
        $userId = $this->getUserId();
        if (!$userId) {
            return $this->success(['count' => 0]);
        }

        $now = date('Y-m-d H:i:s');
        $count = UserCoupon::where('user_id', $userId)
            ->where('status', UserCoupon::STATUS_UNUSED)
            ->where('expired_at', '>', $now)
            ->count();

        return $this->success(['count' => $count]);
    }

    /**
     * 获取可领取的优惠券列表
     * @return Response
     */
    public function claimable(): Response
    {
        $userId = $this->getUserId();
        $now = date('Y-m-d H:i:s');

        // 查找所有有效的优惠券
        $coupons = CouponModel::where('status', 1)
            ->where('start_time', '<=', $now)
            ->where('end_time', '>=', $now)
            ->where(function ($query) {
                $query->where('total_count', 0)
                      ->whereOr('received_count', '<', Db::raw('total_count'));
            })
            ->order('id', 'desc')
            ->select();

        $items = [];
        foreach ($coupons as $coupon) {
            // 检查用户是否已领取
            $claimed = false;
            $claimedCount = 0;
            if ($userId) {
                $claimedCount = UserCoupon::where('user_id', $userId)
                    ->where('coupon_id', $coupon->id)
                    ->count();
                $claimed = $claimedCount >= $coupon->per_limit;
            }

            // 计算剩余数量
            $remaining = $coupon->total_count > 0
                ? $coupon->total_count - $coupon->received_count
                : null;

            // 计算有效天数
            $validDays = max(1, (int) ceil((strtotime($coupon->end_time) - time()) / 86400));

            $items[] = [
                'id' => (int) $coupon->id,
                'name' => $coupon->getTranslated('name', $this->locale),
                'description' => $coupon->getTranslated('description', $this->locale),
                'type' => (int) $coupon->type,
                'value' => (float) $coupon->value,
                'minAmount' => (float) $coupon->min_amount,
                'maxDiscount' => $coupon->max_discount ? (float) $coupon->max_discount : null,
                'scope' => (int) $coupon->scope,
                'validDays' => $validDays,
                'remaining' => $remaining,
                'perLimit' => (int) $coupon->per_limit,
                'claimed' => $claimed,
                'claimedCount' => $claimedCount,
            ];
        }

        return $this->success($items);
    }

    /**
     * 领取优惠券
     * @return Response
     */
    public function claim(): Response
    {
        $userId = $this->getUserId();
        if (!$userId) {
            return $this->error('请先登录', 401);
        }

        $couponId = (int) input('coupon_id');
        if (!$couponId) {
            return $this->error('参数错误');
        }

        $coupon = CouponModel::find($couponId);
        if (!$coupon) {
            return $this->error('优惠券不存在');
        }

        // 检查是否可领取
        if (!$coupon->canReceive()) {
            return $this->error('优惠券已过期或已领完');
        }

        // 检查用户是否已达到领取上限
        $claimedCount = UserCoupon::where('user_id', $userId)
            ->where('coupon_id', $couponId)
            ->count();

        if ($claimedCount >= $coupon->per_limit) {
            return $this->error('您已达到领取上限');
        }

        Db::startTrans();
        try {
            // 增加领取数量
            $coupon->received_count = $coupon->received_count + 1;
            $coupon->save();

            // 计算过期时间
            $expiredAt = min(
                strtotime($coupon->end_time),
                strtotime('+30 days')
            );

            // 创建用户优惠券记录
            $userCoupon = new UserCoupon();
            $userCoupon->user_id = $userId;
            $userCoupon->coupon_id = $couponId;
            $userCoupon->status = UserCoupon::STATUS_UNUSED;
            $userCoupon->received_at = date('Y-m-d H:i:s');
            $userCoupon->expired_at = date('Y-m-d H:i:s', $expiredAt);
            $userCoupon->save();

            Db::commit();

            return $this->success([
                'id' => $userCoupon->id,
                'message' => '领取成功',
            ]);
        } catch (\Exception $e) {
            Db::rollback();
            return $this->error('领取失败：' . $e->getMessage());
        }
    }

    /**
     * 获取订单可用优惠券
     * @return Response
     */
    public function available(): Response
    {
        $userId = $this->getUserId();
        if (!$userId) {
            return $this->error('请先登录', 401);
        }

        $amount = (float) input('amount', 0); // 订单金额
        $goodsIds = input('goods_ids', ''); // 商品ID列表
        $categoryIds = input('category_ids', ''); // 分类ID列表

        $now = date('Y-m-d H:i:s');

        // 使用 LEFT JOIN 支持系统优惠券（coupon_id = 0）
        $list = UserCoupon::alias('uc')
            ->leftJoin('coupons c', 'uc.coupon_id = c.id')
            ->where('uc.user_id', $userId)
            ->where('uc.status', UserCoupon::STATUS_UNUSED)
            ->where('uc.expired_at', '>', $now)
            ->field([
                'uc.id',
                'uc.coupon_id',
                'uc.code',
                'uc.type as uc_type',
                'uc.amount as uc_amount',
                'uc.min_amount as uc_min_amount',
                'uc.source',
                'uc.expired_at',
                'c.name',
                'c.type',
                'c.value',
                'c.min_amount',
                'c.max_discount',
                'c.scope',
                'c.scope_ids',
            ])
            ->order('uc.amount', 'desc')
            ->select();

        $available = [];
        $unavailable = [];

        foreach ($list as $item) {
            // 判断是系统优惠券还是活动优惠券
            $isSystemCoupon = empty($item['coupon_id']) || $item['coupon_id'] == 0;

            if ($isSystemCoupon) {
                // 系统优惠券
                $name = $this->getSystemCouponName($item['source'] ?? '', $this->locale);
                $type = $item['uc_type'] === 'fixed' ? CouponModel::TYPE_FIXED : CouponModel::TYPE_DISCOUNT;
                $value = (float) $item['uc_amount'];
                $minAmount = (float) $item['uc_min_amount'];
                $maxDiscount = null;
                $scope = 0;
            } else {
                // 活动优惠券
                $coupon = CouponModel::find($item['coupon_id']);
                $name = $coupon ? $coupon->getTranslated('name', $this->locale) : $item['name'];
                $type = (int) $item['type'];
                $value = (float) $item['value'];
                $minAmount = (float) $item['min_amount'];
                $maxDiscount = $item['max_discount'] ? (float) $item['max_discount'] : null;
                $scope = (int) $item['scope'];
            }

            $couponData = [
                'id' => (int) $item['id'],
                'couponId' => (int) $item['coupon_id'],
                'code' => $item['code'],
                'name' => $name,
                'type' => $type,
                'value' => $value,
                'minAmount' => $minAmount,
                'maxDiscount' => $maxDiscount,
                'scope' => $scope,
                'source' => $item['source'],
                'isSystemCoupon' => $isSystemCoupon,
                'expiredAt' => $item['expired_at'],
            ];

            // 检查是否满足使用条件
            $canUse = true;
            $reason = '';

            // 检查最低消费
            if ($minAmount > 0 && $amount < $minAmount) {
                $canUse = false;
                $reason = $this->getMinAmountReason($minAmount, $this->locale);
            }

            // TODO: 检查适用范围（分类、商品）

            if ($canUse) {
                // 计算优惠金额
                $discount = $this->calculateDiscount($type, $value, $amount, $maxDiscount);
                $couponData['discount'] = $discount;
                $available[] = $couponData;
            } else {
                $couponData['reason'] = $reason;
                $unavailable[] = $couponData;
            }
        }

        return $this->success([
            'available' => $available,
            'unavailable' => $unavailable,
        ]);
    }

    /**
     * 获取最低消费不满足的提示
     */
    private function getMinAmountReason(float $minAmount, string $locale): string
    {
        $reasons = [
            'en-us' => "Minimum purchase of $" . number_format($minAmount, 2) . " required",
            'zh-tw' => "需滿 $" . number_format($minAmount, 2) . " 才可使用",
            'ja-jp' => "$" . number_format($minAmount, 2) . " 以上のご購入が必要です",
        ];

        return $reasons[$locale] ?? $reasons['en-us'];
    }

    /**
     * 计算优惠金额
     */
    private function calculateDiscount(int $type, float $value, float $amount, ?float $maxDiscount): float
    {
        $discount = 0;

        switch ($type) {
            case CouponModel::TYPE_FIXED:
            case CouponModel::TYPE_AMOUNT:
                // 满减 / 固定金额
                $discount = $value;
                break;
            case CouponModel::TYPE_DISCOUNT:
                // 折扣
                $discount = $amount * (1 - $value);
                if ($maxDiscount && $discount > $maxDiscount) {
                    $discount = $maxDiscount;
                }
                break;
        }

        return round($discount, 2);
    }
}
