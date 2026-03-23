<?php
declare(strict_types=1);

namespace app\api\controller;

use app\common\model\EggTier as EggTierModel;
use app\common\model\EggTierPrize;
use app\common\model\UserPoints;
use app\common\model\PointLog;
use app\common\model\UserWallet;
use app\common\model\Transaction;
use app\common\model\Coupon;
use app\common\model\UserCoupon;
use app\common\model\UserGameChance;
use app\common\model\ChanceLog;
use app\common\model\Game as GameModel;

/**
 * 蛋分级控制器
 */
class EggTier extends Base
{
    /**
     * 获取蛋分级列表
     */
    public function index()
    {
        $tiers = EggTierModel::getActiveTiers();

        $result = [];
        foreach ($tiers as $tier) {
            $tierModel = EggTierModel::find($tier['id']);
            if ($tierModel) {
                $tierData = $tierModel->toApiArray($this->locale);
                // 获取奖品列表
                $prizes = EggTierPrize::where('egg_id', $tier['id'])
                    ->where('status', 1)
                    ->order('sort', 'asc')
                    ->select();
                $tierData['prizes'] = [];
                foreach ($prizes as $prize) {
                    $tierData['prizes'][] = $prize->toApiArray($this->locale);
                }
                $result[] = $tierData;
            }
        }

        return $this->success($result);
    }

    /**
     * 获取蛋详情
     */
    public function detail(string $code)
    {
        $tier = EggTierModel::getByCode($code);
        if (!$tier) {
            return $this->error('Egg tier not found', 404);
        }

        $result = $tier->toApiArray($this->locale);

        // 获取奖品列表
        $prizes = EggTierPrize::where('egg_id', $tier->id)
            ->where('status', 1)
            ->order('sort', 'asc')
            ->select();

        $result['prizes'] = [];
        foreach ($prizes as $prize) {
            $result['prizes'][] = $prize->toApiArray($this->locale);
        }

        return $this->success($result);
    }

    /**
     * 根据订单金额获取可获得的蛋类型
     */
    public function getEggForOrder()
    {
        $amount = (float)$this->request->get('amount', 0);

        $tier = EggTierModel::getEggByOrderAmount($amount);
        if (!$tier) {
            return $this->success(null);
        }

        return $this->success($tier->toApiArray($this->locale));
    }

    /**
     * 砸蛋（使用蛋分级奖池）
     * 这是一个新的砸蛋接口，使用 egg_tier 表的奖池
     * 前端可以根据用户拥有的蛋类型来选择调用此接口
     */
    public function smash()
    {
        if (!$this->userId) {
            return $this->error('Please login first', 401);
        }

        $eggCode = $this->request->post('egg_code', 'bronze_egg');

        // 获取蛋配置
        $tier = EggTierModel::getByCode($eggCode);
        if (!$tier) {
            return $this->error('Egg tier not found', 404);
        }

        // 这里可以添加检查用户是否拥有该类型的蛋的逻辑
        // 目前简化为使用通用的 egg 次数

        // 检查次数
        $chances = UserGameChance::getChances($this->userId, GameModel::CODE_EGG);
        if ($chances <= 0) {
            return $this->error('No chances left', 400);
        }

        // 使用次数
        if (!UserGameChance::useChance($this->userId, GameModel::CODE_EGG)) {
            return $this->error('Failed to use chance', 500);
        }

        // 使用蛋分级奖池抽奖
        $prize = $tier->lottery();

        if (!$prize) {
            return $this->error('Lottery failed', 500);
        }

        // 发放奖品
        $this->issuePrize($prize, $this->userId);

        return $this->success([
            'egg_tier' => $tier->toApiArray($this->locale),
            'prize' => $prize->toApiArray($this->locale),
            'remaining_chances' => UserGameChance::getChances($this->userId, GameModel::CODE_EGG),
        ]);
    }

    /**
     * 发放奖品
     */
    protected function issuePrize(EggTierPrize $prize, int $userId): void
    {
        $prizeType = $prize->getData('type');

        switch ($prizeType) {
            case EggTierPrize::TYPE_POINTS:
                UserPoints::addPoints(
                    $userId,
                    (int)$prize->value,
                    PointLog::SOURCE_GAME,
                    "egg_tier_{$prize->id}",
                    "Egg tier prize"
                );
                break;

            case EggTierPrize::TYPE_CASH:
                // 现金直接发放到用户钱包
                $wallet = UserWallet::getOrCreate($userId);
                $wallet->addBalance((float)$prize->value);
                Transaction::create([
                    'transaction_no' => Transaction::generateNo(),
                    'user_id' => $userId,
                    'type' => Transaction::TYPE_INCOME,
                    'amount' => (float)$prize->value,
                    'balance' => $wallet->balance,
                    'title' => 'Egg Prize',
                    'description' => "Egg tier cash reward",
                    'status' => Transaction::STATUS_SUCCESS,
                    'created_at' => date('Y-m-d H:i:s'),
                ]);
                break;

            case EggTierPrize::TYPE_COUPON:
                if ($prize->coupon_id) {
                    $coupon = Coupon::find($prize->coupon_id);
                    if ($coupon) {
                        UserCoupon::create([
                            'user_id' => $userId,
                            'coupon_id' => $prize->coupon_id,
                            'status' => UserCoupon::STATUS_UNUSED,
                            'received_at' => date('Y-m-d H:i:s'),
                            'expired_at' => date('Y-m-d H:i:s', strtotime('+30 days')),
                        ]);
                    }
                }
                break;

            case EggTierPrize::TYPE_CHANCE:
                $gameCode = $prize->game_code ?? GameModel::CODE_WHEEL;
                UserGameChance::addChances(
                    $userId,
                    $gameCode,
                    (int)$prize->value,
                    ChanceLog::SOURCE_TASK,
                    "egg_tier_{$prize->id}"
                );
                break;
        }
    }
}
