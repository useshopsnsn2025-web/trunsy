<?php
declare(strict_types=1);

namespace app\common\model;

use think\Model;

/**
 * 分期资质申请模型
 */
class CreditApplication extends Model
{
    protected $name = 'credit_applications';

    protected $autoWriteTimestamp = 'datetime';

    protected $createTime = 'created_at';

    protected $updateTime = 'updated_at';

    // 状态常量
    const STATUS_PENDING = 0;      // 待审核
    const STATUS_REVIEWING = 1;    // 审核中
    const STATUS_APPROVED = 2;     // 已通过
    const STATUS_REJECTED = 3;     // 已拒绝
    const STATUS_SUPPLEMENT = 4;   // 需补充资料

    // 证件类型
    const ID_TYPE_PASSPORT = 'passport';
    const ID_TYPE_ID_CARD = 'id_card';
    const ID_TYPE_DRIVER_LICENSE = 'driver_license';

    // 就业状态
    const EMPLOYMENT_EMPLOYED = 'employed';
    const EMPLOYMENT_SELF_EMPLOYED = 'self_employed';
    const EMPLOYMENT_STUDENT = 'student';
    const EMPLOYMENT_RETIRED = 'retired';
    const EMPLOYMENT_OTHER = 'other';

    /**
     * 关联用户
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * 关联审核人
     */
    public function reviewer()
    {
        return $this->belongsTo(Admin::class, 'reviewed_by');
    }

    /**
     * 生成申请编号
     */
    public static function generateApplicationNo(): string
    {
        return 'CA' . date('YmdHis') . str_pad((string)mt_rand(1, 9999), 4, '0', STR_PAD_LEFT);
    }

    /**
     * 获取状态文本
     */
    public function getStatusTextAttr($value, $data): string
    {
        $statusMap = [
            self::STATUS_PENDING => 'Pending Review',
            self::STATUS_REVIEWING => 'Under Review',
            self::STATUS_APPROVED => 'Approved',
            self::STATUS_REJECTED => 'Rejected',
            self::STATUS_SUPPLEMENT => 'Additional Info Required',
        ];
        return $statusMap[$data['status']] ?? 'Unknown';
    }

    /**
     * 获取证件类型文本
     */
    public function getIdTypeTextAttr($value, $data): string
    {
        $typeMap = [
            self::ID_TYPE_PASSPORT => 'Passport',
            self::ID_TYPE_ID_CARD => 'ID Card',
            self::ID_TYPE_DRIVER_LICENSE => 'Driver License',
        ];
        return $typeMap[$data['id_type']] ?? 'Unknown';
    }

    /**
     * 检查用户是否有待处理的申请
     */
    public static function hasPendingApplication(int $userId): bool
    {
        return self::where('user_id', $userId)
            ->whereIn('status', [self::STATUS_PENDING, self::STATUS_REVIEWING, self::STATUS_SUPPLEMENT])
            ->count() > 0;
    }

    /**
     * 获取用户最新的申请
     */
    public static function getLatestByUser(int $userId): ?self
    {
        return self::where('user_id', $userId)
            ->order('created_at', 'desc')
            ->find();
    }
}
