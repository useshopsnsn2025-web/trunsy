<?php
declare(strict_types=1);

namespace app\common\model;

use think\Model;

/**
 * 优惠券翻译模型
 */
class CouponTranslation extends Model
{
    protected $name = 'coupon_translations';
    protected $pk = 'id';
    protected $autoWriteTimestamp = false;

    protected $type = [
        'id' => 'integer',
        'coupon_id' => 'integer',
    ];

    /**
     * 关联优惠券
     */
    public function coupon()
    {
        return $this->belongsTo(Coupon::class, 'coupon_id', 'id');
    }
}
