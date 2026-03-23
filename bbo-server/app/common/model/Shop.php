<?php
declare(strict_types=1);

namespace app\common\model;

use think\Model;
use app\common\traits\Translatable;

/**
 * 店铺模型
 */
class Shop extends Model
{
    use Translatable;

    /**
     * 表名
     * @var string
     */
    protected $name = 'shops';

    /**
     * 主键
     * @var string
     */
    protected $pk = 'id';

    /**
     * 自动时间戳
     * @var bool|string
     */
    protected $autoWriteTimestamp = 'datetime';

    /**
     * 创建时间字段
     * @var string
     */
    protected $createTime = 'created_at';

    /**
     * 更新时间字段
     * @var string
     */
    protected $updateTime = 'updated_at';

    /**
     * 翻译模型
     * @var string
     */
    protected $translationModel = ShopTranslation::class;

    /**
     * 翻译外键
     * @var string
     */
    protected $translationForeignKey = 'shop_id';

    /**
     * 可翻译字段
     * @var array
     */
    protected $translatable = ['name', 'description'];

    /**
     * 类型转换
     * @var array
     */
    protected $type = [
        'rating' => 'float',
        'balance' => 'float',
        'frozen_balance' => 'float',
        'commission_rate' => 'float',
    ];

    /**
     * 关联用户
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * 关联商品
     */
    public function goods()
    {
        return $this->hasMany(Goods::class, 'shop_id');
    }

    /**
     * 转换为 API 数组
     * @param string|null $locale
     * @return array
     */
    public function toApiArray(?string $locale = null): array
    {
        return [
            'id' => $this->id,
            'shopNo' => $this->shop_no,
            'name' => $this->getTranslated('name', $locale),
            'description' => $this->getTranslated('description', $locale),
            'logo' => $this->logo,
            'cover' => $this->cover,
            'type' => (int) $this->type,
            'status' => (int) $this->status,
            'level' => (int) $this->level,
            'salesCount' => (int) $this->sales_count,
            'goodsCount' => (int) $this->goods_count,
            'followersCount' => (int) $this->followers_count,
            'rating' => (float) $this->rating,
            'ratingCount' => (int) $this->rating_count,
        ];
    }
}
