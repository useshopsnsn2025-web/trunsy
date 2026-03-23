<?php
declare(strict_types=1);

namespace app\common\model;

use think\Model;

/**
 * 信用审核日志模型
 */
class CreditReviewLog extends Model
{
    protected $name = 'credit_review_logs';

    protected $autoWriteTimestamp = false;

    protected $json = ['extra'];

    // 操作类型
    const ACTION_START_REVIEW = 'start_review';  // 开始审核
    const ACTION_APPROVE = 'approve';            // 审核通过
    const ACTION_REJECT = 'reject';              // 审核拒绝
    const ACTION_SUPPLEMENT = 'supplement';      // 要求补充

    /**
     * 关联管理员
     */
    public function admin()
    {
        return $this->belongsTo(Admin::class, 'admin_id');
    }

    /**
     * 关联申请
     */
    public function application()
    {
        return $this->belongsTo(CreditApplication::class, 'application_id');
    }

    /**
     * 记录审核日志
     */
    public static function log(
        int $applicationId,
        int $adminId,
        string $action,
        ?string $remark = null,
        ?array $extra = null
    ): self {
        $log = new self();
        $log->data([
            'application_id' => $applicationId,
            'admin_id' => $adminId,
            'action' => $action,
            'remark' => $remark,
            'extra' => $extra ? json_encode($extra) : null,
            'created_at' => date('Y-m-d H:i:s'),
        ]);
        $log->save();

        return $log;
    }

    /**
     * 获取操作类型名称
     */
    public function getActionTextAttr($value, $data): string
    {
        $map = [
            self::ACTION_START_REVIEW => '开始审核',
            self::ACTION_APPROVE => '审核通过',
            self::ACTION_REJECT => '审核拒绝',
            self::ACTION_SUPPLEMENT => '要求补充资料',
        ];
        return $map[$data['action']] ?? $data['action'];
    }
}
