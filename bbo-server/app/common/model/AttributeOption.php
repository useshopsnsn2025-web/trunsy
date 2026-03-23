<?php
declare(strict_types=1);

namespace app\common\model;

use think\Model;
use app\common\traits\Translatable;

/**
 * 属性选项模型
 */
class AttributeOption extends Model
{
    use Translatable;

    /**
     * 表名
     * @var string
     */
    protected $name = 'attribute_options';

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
     * @var bool
     */
    protected $updateTime = false;

    /**
     * 翻译模型
     * @var string
     */
    protected $translationModel = AttributeOptionTranslation::class;

    /**
     * 可翻译字段
     * @var array
     */
    protected $translatable = ['label'];

    /**
     * 获取外键名（覆盖trait方法）
     * @return string
     */
    protected function getTranslationForeignKey(): string
    {
        return 'option_id';
    }

    /**
     * 类型转换
     * @var array
     */
    protected $type = [
        'id' => 'integer',
        'attribute_id' => 'integer',
        'sort' => 'integer',
        'status' => 'integer',
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
