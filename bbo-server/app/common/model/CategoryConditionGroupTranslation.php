<?php
declare(strict_types=1);

namespace app\common\model;

use think\Model;

/**
 * 分类状态组翻译模型
 */
class CategoryConditionGroupTranslation extends Model
{
    /**
     * 表名
     * @var string
     */
    protected $name = 'category_condition_group_translations';

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
        'group_id' => 'integer',
    ];

    /**
     * 关联状态组
     * @return \think\model\relation\BelongsTo
     */
    public function group()
    {
        return $this->belongsTo(CategoryConditionGroup::class, 'group_id', 'id');
    }
}
