<?php
declare(strict_types=1);

namespace app\common\service;

use app\common\model\UserGameChance;
use app\common\model\ChanceLog;
use app\common\model\Game;
use app\common\model\Order;
use app\common\model\UserTreasureBox;
use app\common\model\EggTier;
use app\common\model\InviteRecord;
use think\facade\Log;

/**
 * Game Reward Service
 * Handles game chance distribution based on user activities
 */
class GameRewardService
{
    /**
     * Order amount thresholds for game chances
     * Format: [min_amount => chances]
     */
    const ORDER_CHANCE_THRESHOLDS = [
        150 => 3,  // $150+ = 3 chances
        80 => 2,   // $80+ = 2 chances
        30 => 1,   // $30+ = 1 chance
    ];

    /**
     * Game codes
     */
    const GAME_WHEEL = 'wheel';
    const GAME_EGG = 'egg';
    const GAME_SCRATCH = 'scratch';

    /**
     * Grant game chances after order payment success
     *
     * @param Order $order The paid order
     * @return array Result with granted chances info
     */
    public function grantOrderReward(Order $order): array
    {
        $result = [
            'success' => true,
            'wheel_chances' => 0,
            'egg_code' => null,
            'egg_granted' => false,
        ];

        try {
            $userId = (int)$order->buyer_id;
            $orderAmount = (float)$order->total_amount;
            $orderId = (string)$order->id;
            $orderNo = $order->order_no;

            // 1. Grant wheel chances based on order amount
            $wheelChances = $this->calculateWheelChances($orderAmount);
            if ($wheelChances > 0) {
                UserGameChance::addChances(
                    $userId,
                    self::GAME_WHEEL,
                    $wheelChances,
                    ChanceLog::SOURCE_ORDER,
                    $orderNo
                );
                $result['wheel_chances'] = $wheelChances;
            }

            // 2. Grant egg chance based on order amount tier
            $eggTier = EggTier::getEggByOrderAmount($orderAmount);
            if ($eggTier) {
                // Check if already granted for this order
                $alreadyGranted = ChanceLog::where('user_id', $userId)
                    ->where('game_code', self::GAME_EGG)
                    ->where('source', ChanceLog::SOURCE_ORDER)
                    ->where('source_id', $orderNo)
                    ->find();

                if (!$alreadyGranted) {
                    UserGameChance::addChances(
                        $userId,
                        self::GAME_EGG,
                        1,
                        ChanceLog::SOURCE_ORDER,
                        $orderNo
                    );
                    $result['egg_code'] = $eggTier->code;
                    $result['egg_granted'] = true;
                }
            }

            // 3. Process invite first order reward
            $result['invite_reward_issued'] = false;
            $this->processInviteFirstOrderReward($userId, $order->id, $result);

            Log::info('Game reward granted for order', [
                'order_id' => $orderId,
                'order_no' => $orderNo,
                'user_id' => $userId,
                'amount' => $orderAmount,
                'wheel_chances' => $wheelChances,
                'egg_code' => $result['egg_code'],
                'invite_reward_issued' => $result['invite_reward_issued'],
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to grant game reward for order', [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
            ]);
            $result['success'] = false;
            $result['error'] = $e->getMessage();
        }

        return $result;
    }

    /**
     * Process invite first order reward
     * If this is the user's first order and they were invited, issue rewards
     *
     * @param int $userId User ID
     * @param int $orderId Order ID
     * @param array &$result Result array to update
     */
    protected function processInviteFirstOrderReward(int $userId, int $orderId, array &$result): void
    {
        try {
            // Check if user was invited
            $inviteRecord = InviteRecord::where('invitee_id', $userId)->find();
            if (!$inviteRecord) {
                return; // Not an invited user
            }

            // Check if reward already issued
            if ($inviteRecord->order_reward_issued) {
                return; // Already issued
            }

            // Check if this is user's first completed order
            $orderCount = Order::where('buyer_id', $userId)
                ->where('status', '>=', Order::STATUS_PENDING_SHIPMENT)
                ->count();

            // If this is their first order (count should be 1 after current order)
            if ($orderCount <= 1) {
                // Mark first order and issue reward
                $inviteRecord->markFirstOrder($orderId);
                $inviteRecord->issueFirstOrderReward();
                $result['invite_reward_issued'] = true;

                Log::info('Invite first order reward issued', [
                    'user_id' => $userId,
                    'order_id' => $orderId,
                    'inviter_id' => $inviteRecord->inviter_id,
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Failed to process invite first order reward', [
                'user_id' => $userId,
                'order_id' => $orderId,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Grant game chance after review submission
     *
     * @param int $userId User ID
     * @param int $reviewId Review ID
     * @param string $gameCode Game code (default: wheel)
     * @return array Result
     */
    public function grantReviewReward(int $userId, int $reviewId, string $gameCode = self::GAME_WHEEL): array
    {
        $result = [
            'success' => true,
            'chances' => 0,
        ];

        try {
            // Check if already granted for this review
            $alreadyGranted = ChanceLog::where('user_id', $userId)
                ->where('game_code', $gameCode)
                ->where('source', ChanceLog::SOURCE_TASK)
                ->where('source_id', "review_{$reviewId}")
                ->find();

            if ($alreadyGranted) {
                return $result; // Already granted, skip
            }

            // Grant 1 chance for each review
            UserGameChance::addChances(
                $userId,
                $gameCode,
                1,
                ChanceLog::SOURCE_TASK,
                "review_{$reviewId}"
            );
            $result['chances'] = 1;

            Log::info('Game reward granted for review', [
                'user_id' => $userId,
                'review_id' => $reviewId,
                'game_code' => $gameCode,
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to grant game reward for review', [
                'user_id' => $userId,
                'review_id' => $reviewId,
                'error' => $e->getMessage(),
            ]);
            $result['success'] = false;
            $result['error'] = $e->getMessage();
        }

        return $result;
    }

    /**
     * Grant daily login reward
     *
     * @param int $userId User ID
     * @return array Result
     */
    public function grantDailyLoginReward(int $userId): array
    {
        $result = [
            'success' => true,
            'chances' => 0,
            'already_claimed' => false,
        ];

        try {
            $today = date('Y-m-d');
            $sourceId = "login_{$today}";

            // Check if already claimed today
            $alreadyClaimed = ChanceLog::where('user_id', $userId)
                ->where('game_code', self::GAME_WHEEL)
                ->where('source', ChanceLog::SOURCE_LOGIN)
                ->where('source_id', $sourceId)
                ->find();

            if ($alreadyClaimed) {
                $result['already_claimed'] = true;
                return $result;
            }

            // Grant 1 wheel chance for daily login
            UserGameChance::addChances(
                $userId,
                self::GAME_WHEEL,
                1,
                ChanceLog::SOURCE_LOGIN,
                $sourceId
            );
            $result['chances'] = 1;

        } catch (\Exception $e) {
            Log::error('Failed to grant daily login reward', [
                'user_id' => $userId,
                'error' => $e->getMessage(),
            ]);
            $result['success'] = false;
            $result['error'] = $e->getMessage();
        }

        return $result;
    }

    /**
     * Calculate wheel chances based on order amount
     *
     * @param float $amount Order amount
     * @return int Number of chances
     */
    protected function calculateWheelChances(float $amount): int
    {
        foreach (self::ORDER_CHANCE_THRESHOLDS as $threshold => $chances) {
            if ($amount >= $threshold) {
                return $chances;
            }
        }
        return 0;
    }

    /**
     * Get order reward preview (for display before payment)
     *
     * @param float $amount Order amount
     * @return array Preview info
     */
    public function getOrderRewardPreview(float $amount): array
    {
        $wheelChances = $this->calculateWheelChances($amount);
        $eggTier = EggTier::getEggByOrderAmount($amount);

        return [
            'wheel_chances' => $wheelChances,
            'egg_tier' => $eggTier ? [
                'code' => $eggTier->code,
                'type' => $eggTier->type,
                'name' => $eggTier->getData('name') ?: $eggTier->code,
            ] : null,
        ];
    }
}
