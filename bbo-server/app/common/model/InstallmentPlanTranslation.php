<?php
declare(strict_types=1);

namespace app\common\model;

use think\Model;

/**
 * 分期方案翻译模型
 */
class InstallmentPlanTranslation extends Model
{
    protected $name = 'installment_plan_translations';

    protected $autoWriteTimestamp = false;

    /**
     * 关联方案
     */
    public function plan()
    {
        return $this->belongsTo(InstallmentPlan::class, 'plan_id');
    }
}
