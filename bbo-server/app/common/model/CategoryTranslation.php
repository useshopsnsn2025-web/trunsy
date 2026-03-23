<?php
declare(strict_types=1);

namespace app\common\model;

use think\Model;

/**
 * 分类翻译模型
 */
class CategoryTranslation extends Model
{
    /**
     * 表名
     * @var string
     */
    protected $name = 'category_translations';

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
        'category_id' => 'integer',
    ];

    /**
     * 关联分类
     * @return \think\model\relation\BelongsTo
     */
    public function category()
    {
        return $this->belongsTo(Category::class, 'category_id', 'id');
    }
}
