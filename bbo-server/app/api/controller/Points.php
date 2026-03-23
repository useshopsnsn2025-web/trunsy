<?php
declare(strict_types=1);

namespace app\api\controller;

use app\common\model\UserPoints;
use app\common\model\PointLog;
use app\common\model\Coupon;
use app\common\model\UserCoupon;
use app\common\model\UserGameChance;
use app\common\model\ChanceLog;
use app\common\model\Game;

/**
 * 积分控制器
 */
class Points extends Base
{
    /**
     * 获取积分余额
     */
    public function balance()
    {
        if (!$this->userId) {
            return $this->error('Please login first', 401);
        }

        $points = UserPoints::getOrCreate($this->userId);

        return $this->success($points->toApiArray());
    }

    /**
     * 获取积分记录
     */
    public function logs()
    {
        if (!$this->userId) {
            return $this->error('Please login first', 401);
        }

        $page = (int)$this->request->get('page', 1);
        $pageSize = (int)$this->request->get('page_size', 20);

        $result = PointLog::getUserLogs($this->userId, $page, $pageSize, $this->locale);

        return $this->success($result);
    }

    /**
     * 积分兑换
     */
    public function exchange()
    {
        if (!$this->userId) {
            return $this->error('Please login first', 401);
        }

        $type = $this->request->post('type'); // coupon, chance
        $targetId = (int)$this->request->post('target_id', 0); // 优惠券ID（兑换优惠券时）
        $amount = (int)$this->request->post('amount', 1); // 兑换数量

        if (!$type) {
            return $this->error('Invalid parameters', 400);
        }

        // 根据类型处理
        switch ($type) {
            case 'coupon':
                return $this->exchangeCoupon($targetId, $amount);
            case 'chance':
                return $this->exchangeChance($amount);
            default:
                return $this->error('Invalid exchange type', 400);
        }
    }

    /**
     * 兑换优惠券
     */
    protected function exchangeCoupon(int $couponId, int $amount = 1)
    {
        if ($couponId <= 0) {
            return $this->error('Please select a coupon', 400);
        }

        // 获取优惠券信息
        $coupon = Coupon::find($couponId);
        if (!$coupon || $coupon->status != 1) {
            return $this->error('Coupon not available', 400);
        }

        // 检查优惠券是否可用积分兑换
        if (!$coupon->points_price || $coupon->points_price <= 0) {
            return $this->error('This coupon cannot be exchanged with points', 400);
        }

        $requiredPoints = $coupon->points_price * $amount;

        // 检查积分余额
        $balance = UserPoints::getBalance($this->userId);
        if ($balance < $requiredPoints) {
            return $this->error('Insufficient points', 400);
        }

        // 检查库存
        if ($coupon->stock >= 0 && $coupon->stock < $amount) {
            return $this->error('Coupon out of stock', 400);
        }

        // 扣减积分
        $deducted = UserPoints::deductPoints(
            $this->userId,
            $requiredPoints,
            PointLog::SOURCE_EXCHANGE,
            'coupon_' . $couponId,
            $coupon->getTranslated('name', $this->locale) . ' x' . $amount
        );

        if (!$deducted) {
            return $this->error('Failed to exchange', 500);
        }

        // 发放优惠券
        $expiredAt = date('Y-m-d H:i:s', strtotime('+30 days'));
        for ($i = 0; $i < $amount; $i++) {
            UserCoupon::create([
                'user_id' => $this->userId,
                'coupon_id' => $couponId,
                'status' => UserCoupon::STATUS_UNUSED,
                'received_at' => date('Y-m-d H:i:s'),
                'expired_at' => $expiredAt,
            ]);
        }

        // 更新优惠券领取数量
        $coupon->received_count = $coupon->received_count + $amount;
        if ($coupon->stock > 0) {
            $coupon->stock = $coupon->stock - $amount;
        }
        $coupon->save();

        return $this->success([
            'exchanged' => true,
            'type' => 'coupon',
            'coupon_name' => $coupon->getTranslated('name', $this->locale),
            'amount' => $amount,
            'points_used' => $requiredPoints,
            'remaining_balance' => UserPoints::getBalance($this->userId),
        ]);
    }

    /**
     * 兑换游戏次数
     */
    protected function exchangeChance(int $amount = 1)
    {
        if ($amount <= 0 || $amount > 10) {
            return $this->error('Invalid amount (1-10)', 400);
        }

        // 兑换比例：200积分 = 1次转盘
        $pointsPerChance = 200;
        $requiredPoints = $pointsPerChance * $amount;

        // 检查积分余额
        $balance = UserPoints::getBalance($this->userId);
        if ($balance < $requiredPoints) {
            return $this->error('Insufficient points', 400);
        }

        // 扣减积分
        $deducted = UserPoints::deductPoints(
            $this->userId,
            $requiredPoints,
            PointLog::SOURCE_EXCHANGE,
            'chance_' . $amount,
            "Wheel spin x{$amount}"
        );

        if (!$deducted) {
            return $this->error('Failed to exchange', 500);
        }

        // 发放游戏次数
        UserGameChance::addChances(
            $this->userId,
            Game::CODE_WHEEL,
            $amount,
            ChanceLog::SOURCE_EXCHANGE,
            '',
            $this->request->ip()
        );

        return $this->success([
            'exchanged' => true,
            'type' => 'chance',
            'amount' => $amount,
            'points_used' => $requiredPoints,
            'remaining_balance' => UserPoints::getBalance($this->userId),
            'total_chances' => UserGameChance::getChances($this->userId, Game::CODE_WHEEL),
        ]);
    }

    /**
     * 获取可兑换项目列表
     */
    public function exchangeItems()
    {
        if (!$this->userId) {
            return $this->error('Please login first', 401);
        }

        $balance = UserPoints::getBalance($this->userId);

        // 获取可用积分兑换的优惠券
        $coupons = Coupon::where('status', 1)
            ->where('points_price', '>', 0)
            ->where(function ($query) {
                $query->where('stock', -1)->whereOr('stock', '>', 0);
            })
            ->order('points_price', 'asc')
            ->select();

        $couponList = [];
        foreach ($coupons as $coupon) {
            $couponList[] = [
                'id' => $coupon->id,
                'name' => $coupon->getTranslated('name', $this->locale),
                'description' => $coupon->getTranslated('description', $this->locale),
                'type' => $coupon->getData('type'),
                'value' => (float)$coupon->value,
                'min_amount' => (float)$coupon->min_amount,
                'points_price' => (int)$coupon->points_price,
                'stock' => $coupon->stock,
                'can_exchange' => $balance >= $coupon->points_price,
            ];
        }

        // 游戏次数兑换选项
        $chanceOptions = [
            [
                'amount' => 1,
                'points_price' => 200,
                'can_exchange' => $balance >= 200,
            ],
            [
                'amount' => 5,
                'points_price' => 1000,
                'can_exchange' => $balance >= 1000,
            ],
            [
                'amount' => 10,
                'points_price' => 2000,
                'can_exchange' => $balance >= 2000,
            ],
        ];

        return $this->success([
            'balance' => $balance,
            'coupons' => $couponList,
            'chances' => $chanceOptions,
        ]);
    }
}
