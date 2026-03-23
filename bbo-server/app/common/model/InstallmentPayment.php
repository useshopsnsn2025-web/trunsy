<?php
declare(strict_types=1);

namespace app\common\model;

use think\Model;

/**
 * 还款记录模型
 */
class InstallmentPayment extends Model
{
    protected $name = 'installment_payments';

    protected $autoWriteTimestamp = false;

    protected $createTime = 'created_at';

    // 状态常量
    const STATUS_PENDING = 0;    // 处理中
    const STATUS_SUCCESS = 1;    // 成功
    const STATUS_FAILED = 2;     // 失败

    /**
     * 关联分期订单
     */
    public function installment()
    {
        return $this->belongsTo(InstallmentOrder::class, 'installment_id');
    }

    /**
     * 关联还款计划
     */
    public function schedule()
    {
        return $this->belongsTo(InstallmentSchedule::class, 'schedule_id');
    }

    /**
     * 关联用户
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * 生成还款单号
     */
    public static function generatePaymentNo(): string
    {
        return 'IP' . date('YmdHis') . str_pad((string)mt_rand(1, 9999), 4, '0', STR_PAD_LEFT);
    }

    /**
     * 获取状态文本
     */
    public function getStatusTextAttr($value, $data): string
    {
        $statusMap = [
            self::STATUS_PENDING => 'Processing',
            self::STATUS_SUCCESS => 'Success',
            self::STATUS_FAILED => 'Failed',
        ];
        return $statusMap[$data['status']] ?? 'Unknown';
    }

    /**
     * 标记成功
     */
    public function markSuccess(string $transactionId = null): void
    {
        $this->status = self::STATUS_SUCCESS;
        $this->paid_at = date('Y-m-d H:i:s');
        if ($transactionId) {
            $this->transaction_id = $transactionId;
        }
        $this->save();

        // 更新还款计划
        $schedule = $this->schedule;
        $schedule->markPaid($this->amount, $this->paid_at);
    }

    /**
     * 标记失败
     */
    public function markFailed(string $reason = null): void
    {
        $this->status = self::STATUS_FAILED;
        $this->failure_reason = $reason;
        $this->save();
    }
}
