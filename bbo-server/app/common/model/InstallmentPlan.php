<?php
declare(strict_types=1);

namespace app\common\model;

use think\Model;

/**
 * 分期方案模型
 */
class InstallmentPlan extends Model
{
    protected $name = 'installment_plans';

    protected $autoWriteTimestamp = 'datetime';

    /**
     * 获取翻译
     */
    public function translations()
    {
        return $this->hasMany(InstallmentPlanTranslation::class, 'plan_id');
    }

    /**
     * 获取可用的分期方案
     */
    public static function getAvailablePlans(float $amount, int $creditLevel = 1, string $locale = 'en-us'): array
    {
        $plans = self::where('is_enabled', 1)
            ->where('min_amount', '<=', $amount)
            ->where(function ($query) use ($amount) {
                $query->whereNull('max_amount')
                    ->whereOr('max_amount', '>=', $amount);
            })
            ->where('min_credit_level', '<=', $creditLevel)
            ->order('sort', 'desc')
            ->with('translations')
            ->select();

        $result = [];
        foreach ($plans as $plan) {
            $interestRate = (float) $plan->interest_rate;
            $feeRate = (float) $plan->fee_rate;
            $periodAmount = self::calculatePeriodAmount($amount, (int) $plan->periods, $interestRate, $feeRate);
            $totalAmount = $periodAmount * $plan->periods;
            $totalFee = $amount * $feeRate;
            $totalInterest = $totalAmount - $amount - $totalFee;

            // 获取翻译后的名称和描述
            $name = $plan->name;
            $description = $plan->description;

            // 查找对应语言的翻译
            $normalizedLocale = strtolower($locale);
            if ($plan->translations) {
                foreach ($plan->translations as $trans) {
                    if (strtolower($trans->locale) === $normalizedLocale) {
                        if (!empty($trans->name)) {
                            $name = $trans->name;
                        }
                        if (!empty($trans->description)) {
                            $description = $trans->description;
                        }
                        break;
                    }
                }
            }

            $result[] = [
                'id' => $plan->id,
                'name' => $name,
                'description' => $description,
                'periods' => $plan->periods,
                'interest_rate' => $plan->interest_rate,
                'fee_rate' => $plan->fee_rate,
                'period_amount' => round($periodAmount, 2),
                'total_amount' => round($totalAmount, 2),
                'total_interest' => round($totalInterest, 2),
                'total_fee' => round($totalFee, 2),
                'is_interest_free' => $plan->interest_rate == 0,
            ];
        }

        return $result;
    }

    /**
     * 计算每期金额
     */
    public static function calculatePeriodAmount(float $principal, int $periods, float $interestRate, float $feeRate): float
    {
        $fee = $principal * $feeRate;
        $totalWithFee = $principal + $fee;

        if ($interestRate == 0) {
            // 无息分期
            return $totalWithFee / $periods;
        }

        // 等额本息计算
        $monthlyRate = $interestRate;
        $factor = pow(1 + $monthlyRate, $periods);
        return ($totalWithFee * $monthlyRate * $factor) / ($factor - 1);
    }

    /**
     * 计算分期详情
     */
    public static function calculateInstallmentDetail(float $amount, int $planId, string $locale = 'zh-CN'): ?array
    {
        $plan = self::find($planId);
        if (!$plan || !$plan->is_enabled) {
            return null;
        }

        if ($amount < $plan->min_amount) {
            return null;
        }

        if ($plan->max_amount && $amount > $plan->max_amount) {
            return null;
        }

        $interestRate = (float) $plan->interest_rate;
        $feeRate = (float) $plan->fee_rate;
        $periods = (int) $plan->periods;

        $periodAmount = self::calculatePeriodAmount($amount, $periods, $interestRate, $feeRate);
        $totalAmount = $periodAmount * $periods;
        $totalFee = $amount * $feeRate;
        $totalInterest = $totalAmount - $amount - $totalFee;

        // 生成还款计划预览
        $schedules = [];
        $remainingPrincipal = $amount;
        $principalPerPeriod = $amount / $periods;
        $feePerPeriod = $totalFee / $periods;

        for ($i = 1; $i <= $periods; $i++) {
            $interest = $remainingPrincipal * $interestRate;
            $principal = $principalPerPeriod;
            $fee = $feePerPeriod;

            $schedules[] = [
                'period' => $i,
                'principal' => round($principal, 2),
                'interest' => round($interest, 2),
                'fee' => round($fee, 2),
                'amount' => round($principal + $interest + $fee, 2),
                'due_date' => date('Y-m-d', strtotime("+{$i} months")),
            ];

            $remainingPrincipal -= $principal;
        }

        // 根据语言选择名称
        $planName = $plan->name;
        if (strtolower($locale) === 'en-us' && $plan->name_en) {
            $planName = $plan->name_en;
        }

        return [
            'plan_id' => $plan->id,
            'plan_name' => $planName,
            'periods' => $periods,
            'original_amount' => $amount,
            'period_amount' => round($periodAmount, 2),
            'total_amount' => round($totalAmount, 2),
            'total_interest' => round(max(0, $totalInterest), 2),
            'total_fee' => round($totalFee, 2),
            'is_interest_free' => $interestRate == 0,
            'schedules' => $schedules,
        ];
    }
}
