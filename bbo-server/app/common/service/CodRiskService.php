<?php
declare(strict_types=1);

namespace app\common\service;

use app\common\model\Order;
use app\common\model\CodRiskRule;

/**
 * COD风险评估服务
 * 评估用户使用货到付款的风险，决定是否需要预授权
 */
class CodRiskService
{
    /**
     * 评估COD风险
     * @param int $userId 用户ID
     * @param float $amount 订单金额
     * @param string $countryCode 国家代码
     * @return array
     */
    public function assess(int $userId, float $amount, string $countryCode = 'US'): array
    {
        $score = 0;
        $flags = [];
        $rules = CodRiskRule::getEnabledRules();

        foreach ($rules as $rule) {
            $result = $this->evaluateRule($rule, $userId, $amount, $countryCode);
            if ($result['matched']) {
                $score += $rule->risk_score;
                $flags[] = $rule->rule_type;

                // 如果是阻止规则，直接返回
                if ($rule->action === CodRiskRule::ACTION_BLOCK) {
                    return [
                        'blocked' => true,
                        'reason' => $rule->rule_type,
                        'score' => $score,
                        'flags' => $flags,
                    ];
                }
            }
        }

        // COD预授权策略（零预付模式）：
        // 所有COD订单都使用预授权机制，确保商家权益
        // 预授权金额 = 订单总金额（包含运费）
        return [
            'blocked' => false,
            'score' => $score,
            'flags' => $flags,
            'require_preauth' => true,  // COD模式下始终要求预授权
            'preauth_amount' => $amount,
        ];
    }

    /**
     * 评估单条规则
     * @param CodRiskRule $rule
     * @param int $userId
     * @param float $amount
     * @param string $countryCode
     * @return array
     */
    protected function evaluateRule(CodRiskRule $rule, int $userId, float $amount, string $countryCode): array
    {
        $config = $rule->rule_config;

        switch ($rule->rule_type) {
            case CodRiskRule::TYPE_NEW_USER:
                return $this->checkNewUser($userId, $config);

            case CodRiskRule::TYPE_HIGH_AMOUNT:
                return $this->checkHighAmount($amount, $config);

            case CodRiskRule::TYPE_HISTORY_REFUSED:
                return $this->checkHistoryRefused($userId, $config);

            case CodRiskRule::TYPE_DAILY_LIMIT:
                return $this->checkDailyLimit($userId, $config);

            case CodRiskRule::TYPE_REGION:
                return $this->checkRegion($countryCode, $config);

            default:
                return ['matched' => false];
        }
    }

    /**
     * 检查是否为新用户
     */
    protected function checkNewUser(int $userId, array $config): array
    {
        $minOrders = $config['min_completed_orders'] ?? 0;
        $completedOrders = Order::where('buyer_id', $userId)
            ->where('status', Order::STATUS_COMPLETED)
            ->count();

        return ['matched' => $completedOrders <= $minOrders];
    }

    /**
     * 检查是否为高金额订单
     */
    protected function checkHighAmount(float $amount, array $config): array
    {
        $maxAmount = $config['max_amount'] ?? 500;
        return ['matched' => $amount > $maxAmount];
    }

    /**
     * 检查是否有拒收历史
     */
    protected function checkHistoryRefused(int $userId, array $config): array
    {
        $maxRefused = $config['max_refused'] ?? 1;
        $refusedCount = Order::where('buyer_id', $userId)
            ->where('payment_type', Order::PAYMENT_TYPE_COD)
            ->where('cod_status', Order::COD_STATUS_REFUSED)
            ->count();

        return ['matched' => $refusedCount >= $maxRefused];
    }

    /**
     * 检查每日COD订单限制
     */
    protected function checkDailyLimit(int $userId, array $config): array
    {
        $maxDaily = $config['max_daily'] ?? 3;
        $todayCount = Order::where('buyer_id', $userId)
            ->where('payment_type', Order::PAYMENT_TYPE_COD)
            ->whereDay('created_at', date('Y-m-d'))
            ->count();

        return ['matched' => $todayCount >= $maxDaily];
    }

    /**
     * 检查地区限制
     */
    protected function checkRegion(string $countryCode, array $config): array
    {
        $blockedRegions = $config['blocked_regions'] ?? [];
        return ['matched' => in_array($countryCode, $blockedRegions)];
    }
}
