<?php
declare(strict_types=1);

namespace app\common\model;

use think\Model;
use app\common\traits\Translatable;

/**
 * 分类状态选项模型
 */
class CategoryConditionOption extends Model
{
    use Translatable;

    /**
     * 表名
     * @var string
     */
    protected $name = 'category_condition_options';

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
    protected $translationModel = CategoryConditionOptionTranslation::class;

    /**
     * 翻译外键
     * @var string
     */
    protected $translationForeignKey = 'option_id';

    /**
     * 可翻译字段
     * @var array
     */
    protected $translatable = ['name'];

    /**
     * 类型转换
     * @var array
     */
    protected $type = [
        'id' => 'integer',
        'group_id' => 'integer',
        'sort' => 'integer',
        'impact_level' => 'integer',
        'status' => 'integer',
    ];

    /**
     * 影响等级映射
     */
    const IMPACT_LEVELS = [
        1 => 'excellent',  // 优秀
        2 => 'good',       // 良好
        3 => 'fair',       // 一般
        4 => 'poor',       // 较差
        5 => 'bad',        // 很差
    ];

    /**
     * 关联状态组
     * @return \think\model\relation\BelongsTo
     */
    public function group()
    {
        return $this->belongsTo(CategoryConditionGroup::class, 'group_id', 'id');
    }

    /**
     * 获取影响等级名称
     * @return string|null
     */
    public function getImpactLevelNameAttr(): ?string
    {
        return self::IMPACT_LEVELS[$this->impact_level] ?? null;
    }

    /**
     * 转换为API数组
     * @param string|null $locale
     * @return array
     */
    public function toApiArray(?string $locale = null): array
    {
        $locale = $locale ?? app()->getLang();

        return [
            'id' => $this->id,
            'group_id' => $this->group_id,
            'name' => $this->getTranslated('name', $locale),
            'sort' => $this->sort,
            'impact_level' => $this->impact_level,
            'impact_level_name' => $this->impact_level_name,
            'status' => $this->status,
        ];
    }
}
