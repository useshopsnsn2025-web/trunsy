<?php
declare(strict_types=1);

namespace app\common\model;

use think\Model;

/**
 * 属性选项翻译模型
 */
class AttributeOptionTranslation extends Model
{
    /**
     * 表名
     * @var string
     */
    protected $name = 'attribute_option_translations';

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
        'option_id' => 'integer',
    ];

    /**
     * 关联选项
     * @return \think\model\relation\BelongsTo
     */
    public function option()
    {
        return $this->belongsTo(AttributeOption::class, 'option_id', 'id');
    }
}
