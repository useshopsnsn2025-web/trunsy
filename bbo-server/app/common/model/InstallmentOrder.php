<?php
declare(strict_types=1);

namespace app\common\model;

use think\Model;

/**
 * 分期订单模型
 */
class InstallmentOrder extends Model
{
    protected $name = 'installment_orders';

    protected $autoWriteTimestamp = 'datetime';

    // 状态常量
    const STATUS_ACTIVE = 1;      // 进行中
    const STATUS_COMPLETED = 2;   // 已完成
    const STATUS_OVERDUE = 3;     // 逾期
    const STATUS_CANCELLED = 4;   // 已取消

    /**
     * 关联用户
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * 关联商品订单
     */
    public function order()
    {
        return $this->belongsTo(Order::class, 'order_id');
    }

    /**
     * 关联分期方案
     */
    public function plan()
    {
        return $this->belongsTo(InstallmentPlan::class, 'plan_id');
    }

    /**
     * 关联还款计划
     */
    public function schedules()
    {
        return $this->hasMany(InstallmentSchedule::class, 'installment_id');
    }

    /**
     * 关联还款记录
     */
    public function payments()
    {
        return $this->hasMany(InstallmentPayment::class, 'installment_id');
    }

    /**
     * 生成分期单号
     */
    public static function generateInstallmentNo(): string
    {
        return 'IN' . date('YmdHis') . str_pad((string)mt_rand(1, 9999), 4, '0', STR_PAD_LEFT);
    }

    /**
     * 获取状态文本
     */
    public function getStatusTextAttr($value, $data): string
    {
        $statusMap = [
            self::STATUS_ACTIVE => 'Active',
            self::STATUS_COMPLETED => 'Completed',
            self::STATUS_OVERDUE => 'Overdue',
            self::STATUS_CANCELLED => 'Cancelled',
        ];
        return $statusMap[$data['status']] ?? 'Unknown';
    }

    /**
     * 获取下一期待还款计划
     */
    public function getNextSchedule(): ?InstallmentSchedule
    {
        return InstallmentSchedule::where('installment_id', $this->id)
            ->where('status', InstallmentSchedule::STATUS_PENDING)
            ->order('period', 'asc')
            ->find();
    }

    /**
     * 更新下期还款日
     */
    public function updateNextDueDate(): void
    {
        $nextSchedule = $this->getNextSchedule();
        if ($nextSchedule) {
            $this->next_due_date = $nextSchedule->due_date;
        } else {
            $this->next_due_date = null;
        }
        $this->save();
    }

    /**
     * 检查并更新逾期状态
     */
    public function checkOverdue(): void
    {
        $overdueSchedules = InstallmentSchedule::where('installment_id', $this->id)
            ->where('status', InstallmentSchedule::STATUS_PENDING)
            ->where('due_date', '<', date('Y-m-d'))
            ->select();

        if ($overdueSchedules->count() > 0) {
            $this->status = self::STATUS_OVERDUE;

            // 计算逾期天数
            $earliestOverdue = $overdueSchedules->first();
            $overdueDays = $earliestOverdue->due_date
                ? (strtotime(date('Y-m-d')) - strtotime($earliestOverdue->due_date)) / 86400
                : 0;
            $this->overdue_days = (int)$overdueDays;
            $this->max_overdue_days = max($this->max_overdue_days, $this->overdue_days);

            // 更新逾期的还款计划状态
            foreach ($overdueSchedules as $schedule) {
                $schedule->status = InstallmentSchedule::STATUS_OVERDUE;
                // TODO: 计算滞纳金
                $schedule->save();
            }

            $this->save();
        }
    }

    /**
     * 完成分期订单
     */
    public function markCompleted(): void
    {
        $this->status = self::STATUS_COMPLETED;
        $this->completed_at = date('Y-m-d H:i:s');
        $this->next_due_date = null;
        $this->save();

        // 释放用户额度
        $creditLimit = UserCreditLimit::getByUser($this->user_id);
        if ($creditLimit) {
            $creditLimit->releaseLimit($this->financed_amount);
        }
    }
}
