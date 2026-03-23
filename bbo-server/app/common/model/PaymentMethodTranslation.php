<?php
declare(strict_types=1);

namespace app\common\model;

use think\Model;

/**
 * 支付方式翻译模型
 */
class PaymentMethodTranslation extends Model
{
    protected $name = 'payment_method_translations';
    protected $pk = 'id';
    protected $autoWriteTimestamp = 'datetime';
    protected $createTime = 'created_at';
    protected $updateTime = 'updated_at';

    protected $type = [
        'id' => 'integer',
        'payment_method_id' => 'integer',
        'is_original' => 'integer',
        'is_auto_translated' => 'integer',
    ];

    /**
     * 关联支付方式
     */
    public function paymentMethod()
    {
        return $this->belongsTo(PaymentMethod::class, 'payment_method_id', 'id');
    }
}
