<?php
declare(strict_types=1);

namespace app\common\model;

use think\Model;

/**
 * 店铺翻译模型
 */
class ShopTranslation extends Model
{
    /**
     * 表名
     * @var string
     */
    protected $name = 'shop_translations';

    /**
     * 主键
     * @var string
     */
    protected $pk = 'id';

    /**
     * 自动时间戳
     * @var bool
     */
    protected $autoWriteTimestamp = false;

    /**
     * 关联店铺
     */
    public function shop()
    {
        return $this->belongsTo(Shop::class, 'shop_id');
    }
}
