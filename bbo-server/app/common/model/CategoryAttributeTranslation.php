<?php
declare(strict_types=1);

namespace app\common\model;

use think\Model;

/**
 * 品类属性翻译模型
 */
class CategoryAttributeTranslation extends Model
{
    /**
     * 表名
     * @var string
     */
    protected $name = 'category_attribute_translations';

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
     * 类型转换
     * @var array
     */
    protected $type = [
        'id' => 'integer',
        'attribute_id' => 'integer',
    ];

    /**
     * 关联属性
     * @return \think\model\relation\BelongsTo
     */
    public function attribute()
    {
        return $this->belongsTo(CategoryAttribute::class, 'attribute_id', 'id');
    }
}
