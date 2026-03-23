<?php
declare(strict_types=1);

namespace app\common\model;

use think\Model;

/**
 * 分类状态选项翻译模型
 */
class CategoryConditionOptionTranslation extends Model
{
    /**
     * 表名
     * @var string
     */
    protected $name = 'category_condition_option_translations';

    /**
     * 主键
     * @var string
     */
    protected $pk = 'id';

    /**
     * 关闭自动时间戳
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
        return $this->belongsTo(CategoryConditionOption::class, 'option_id', 'id');
    }
}
