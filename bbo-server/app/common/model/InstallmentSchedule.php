<?php
declare(strict_types=1);

namespace app\common\model;

use think\Model;

/**
 * 还款计划模型
 */
class InstallmentSchedule extends Model
{
    protected $name = 'installment_schedules';

    protected $autoWriteTimestamp = 'datetime';

    // 状态常量
    const STATUS_PENDING = 0;     // 待还
    const STATUS_PAID = 1;        // 已还
    const STATUS_OVERDUE = 2;     // 逾期
    const STATUS_PARTIAL = 3;     // 部分还款

    /**
     * 关联分期订单
     */
    public function installment()
    {
        return $this->belongsTo(InstallmentOrder::class, 'installment_id');
    }

    /**
     * 关联还款记录
     */
    public function payments()
    {
        return $this->hasMany(InstallmentPayment::class, 'schedule_id');
    }

    /**
     * 获取状态文本
     */
    public function getStatusTextAttr($value, $data): string
    {
        $statusMap = [
            self::STATUS_PENDING => 'Pending',
            self::STATUS_PAID => 'Paid',
            self::STATUS_OVERDUE => 'Overdue',
            self::STATUS_PARTIAL => 'Partial',
        ];
        return $statusMap[$data['status']] ?? 'Unknown';
    }

    /**
     * 获取剩余应还金额
     */
    public function getRemainingAmountAttr($value, $data): float
    {
        return max(0, $data['amount'] + $data['late_fee'] - $data['paid_amount']);
    }

    /**
     * 是否已逾期
     */
    public function isOverdue(): bool
    {
        return $this->status == self::STATUS_PENDING && $this->due_date && strtotime($this->due_date) < strtotime(date('Y-m-d'));
    }

    /**
     * 计算滞纳金
     */
    public function calculateLateFee(): float
    {
        if (!$this->isOverdue() || !$this->due_date) {
            return 0;
        }

        $overdueDays = (strtotime(date('Y-m-d')) - strtotime($this->due_date)) / 86400;
        // 滞纳金：每天0.05%，最高不超过本期应还金额的10%
        $lateFeeRate = 0.0005;
        $maxLateFee = $this->amount * 0.1;
        $lateFee = $this->amount * $lateFeeRate * $overdueDays;

        return min($lateFee, $maxLateFee);
    }

    /**
     * 更新滞纳金
     */
    public function updateLateFee(): void
    {
        $lateFee = $this->calculateLateFee();
        if ($lateFee > $this->late_fee) {
            $this->late_fee = $lateFee;
            $this->save();
        }
    }

    /**
     * 完成还款
     */
    public function markPaid(float $amount, string $paidAt = null): void
    {
        $this->paid_amount += $amount;
        $this->paid_at = $paidAt ?? date('Y-m-d H:i:s');

        if ($this->paid_amount >= $this->amount + $this->late_fee) {
            $this->status = self::STATUS_PAID;
        } else {
            $this->status = self::STATUS_PARTIAL;
        }

        $this->save();

        // 更新分期订单
        $installment = $this->installment;
        if ($this->status == self::STATUS_PAID) {
            $installment->paid_periods += 1;

            // 检查是否全部还清
            if ($installment->paid_periods >= $installment->periods) {
                $installment->markCompleted();
            } else {
                // 重置逾期天数并更新下期还款日
                $installment->overdue_days = 0;
                $installment->status = InstallmentOrder::STATUS_ACTIVE;
                $installment->updateNextDueDate();
            }
        }
    }
}
