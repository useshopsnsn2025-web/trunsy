<?php
declare(strict_types=1);

namespace app\common\model;

use think\Model;

/**
 * 品牌翻译模型
 */
class BrandTranslation extends Model
{
    protected $name = 'brand_translations';
    protected $pk = 'id';
    protected $autoWriteTimestamp = false;

    protected $type = [
        'id' => 'integer',
        'brand_id' => 'integer',
    ];

    /**
     * 关联品牌
     */
    public function brand()
    {
        return $this->belongsTo(Brand::class, 'brand_id', 'id');
    }
}
