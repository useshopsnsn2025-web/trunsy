<?php
declare(strict_types=1);

namespace app\common\model;

use think\Model;

/**
 * 物流运输商翻译模型
 */
class ShippingCarrierTranslation extends Model
{
    protected $name = 'shipping_carrier_translations';
    protected $pk = 'id';
    protected $autoWriteTimestamp = 'datetime';
    protected $createTime = 'created_at';
    protected $updateTime = 'updated_at';

    protected $type = [
        'id' => 'integer',
        'carrier_id' => 'integer',
        'is_original' => 'integer',
        'is_auto_translated' => 'integer',
    ];

    /**
     * 关联运输商
     */
    public function carrier()
    {
        return $this->belongsTo(ShippingCarrier::class, 'carrier_id', 'id');
    }
}
