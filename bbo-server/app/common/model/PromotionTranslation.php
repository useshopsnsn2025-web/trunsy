<?php
declare(strict_types=1);

namespace app\common\model;

use think\Model;

/**
 * 活动翻译模型
 */
class PromotionTranslation extends Model
{
    protected $name = 'promotion_translations';
    protected $pk = 'id';
    protected $autoWriteTimestamp = false;

    protected $type = [
        'id' => 'integer',
        'promotion_id' => 'integer',
    ];

    /**
     * 关联活动
     */
    public function promotion()
    {
        return $this->belongsTo(Promotion::class, 'promotion_id', 'id');
    }
}
