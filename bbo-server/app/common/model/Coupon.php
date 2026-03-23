<?php
declare(strict_types=1);

namespace app\common\model;

use think\Model;
use app\common\traits\Translatable;

/**
 * 优惠券模型
 */
class Coupon extends Model
{
    use Translatable;

    protected $name = 'coupons';
    protected $pk = 'id';
    protected $autoWriteTimestamp = 'datetime';
    protected $createTime = 'created_at';
    protected $updateTime = 'updated_at';

    /**
     * 翻译模型
     */
    protected $translationModel = CouponTranslation::class;

    /**
     * 可翻译字段
     */
    protected $translatable = ['name', 'description'];

    protected $type = [
        'id' => 'integer',
        'type' => 'integer',
        'value' => 'float',
        'min_amount' => 'float',
        'max_discount' => 'float',
        'total_count' => 'integer',
        'used_count' => 'integer',
        'received_count' => 'integer',
        'per_limit' => 'integer',
        'scope' => 'integer',
        'scope_ids' => 'json',
        'status' => 'integer',
        'image' => 'string',
    ];

    // 类型常量
    const TYPE_FIXED = 1;      // 满减
    const TYPE_DISCOUNT = 2;   // 折扣
    const TYPE_AMOUNT = 3;     // 固定金额

    // 适用范围常量
    const SCOPE_ALL = 1;       // 全场
    const SCOPE_CATEGORY = 2;  // 指定分类
    const SCOPE_GOODS = 3;     // 指定商品

    /**
     * 类型名称
     */
    public static function getTypeNames(): array
    {
        return [
            self::TYPE_FIXED => '满减券',
            self::TYPE_DISCOUNT => '折扣券',
            self::TYPE_AMOUNT => '代金券',
        ];
    }

    /**
     * 判断是否可领取
     */
    public function canReceive(): bool
    {
        if ($this->status != 1) return false;
        if ($this->total_count > 0 && $this->received_count >= $this->total_count) return false;
        if (strtotime($this->start_time) > time()) return false;
        if (strtotime($this->end_time) < time()) return false;
        return true;
    }
}
