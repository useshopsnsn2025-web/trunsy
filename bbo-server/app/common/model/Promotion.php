<?php
declare(strict_types=1);

namespace app\common\model;

use think\Model;
use app\common\traits\Translatable;

/**
 * 营销活动模型
 */
class Promotion extends Model
{
    use Translatable;

    protected $name = 'promotions';
    protected $pk = 'id';
    protected $autoWriteTimestamp = 'datetime';
    protected $createTime = 'created_at';
    protected $updateTime = 'updated_at';

    /**
     * 翻译模型
     */
    protected $translationModel = PromotionTranslation::class;

    /**
     * 可翻译字段
     */
    protected $translatable = ['name', 'description'];

    protected $type = [
        'id' => 'integer',
        'type' => 'integer',
        'rules' => 'json',
        'status' => 'integer',
        'sort' => 'integer',
    ];

    // 活动类型常量
    const TYPE_DISCOUNT = 1;   // 限时折扣
    const TYPE_REDUCTION = 2;  // 满减活动
    const TYPE_SECKILL = 3;    // 秒杀
    const TYPE_GROUP = 4;      // 拼团

    // 状态常量
    const STATUS_DRAFT = 0;    // 草稿
    const STATUS_RUNNING = 1;  // 进行中
    const STATUS_ENDED = 2;    // 已结束
    const STATUS_CANCELLED = 3; // 已取消

    public static function getTypeNames(): array
    {
        return [
            self::TYPE_DISCOUNT => '限时折扣',
            self::TYPE_REDUCTION => '满减活动',
            self::TYPE_SECKILL => '秒杀',
            self::TYPE_GROUP => '拼团',
        ];
    }

    public static function getStatusNames(): array
    {
        return [
            self::STATUS_DRAFT => '草稿',
            self::STATUS_RUNNING => '进行中',
            self::STATUS_ENDED => '已结束',
            self::STATUS_CANCELLED => '已取消',
        ];
    }

    /**
     * 关联活动商品
     */
    public function goods()
    {
        return $this->hasMany(PromotionGoods::class, 'promotion_id', 'id');
    }
}
