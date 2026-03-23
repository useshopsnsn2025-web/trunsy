<?php
declare(strict_types=1);

namespace app\common\model;

use think\Model;
use app\common\traits\Translatable;

/**
 * 分类状态组模型
 */
class CategoryConditionGroup extends Model
{
    use Translatable;

    /**
     * 表名
     * @var string
     */
    protected $name = 'category_condition_groups';

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
    protected $translationModel = CategoryConditionGroupTranslation::class;

    /**
     * 翻译外键
     * @var string
     */
    protected $translationForeignKey = 'group_id';

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
        'category_id' => 'integer',
        'sort' => 'integer',
        'is_required' => 'integer',
        'status' => 'integer',
    ];

    /**
     * 关联分类
     * @return \think\model\relation\BelongsTo
     */
    public function category()
    {
        return $this->belongsTo(Category::class, 'category_id', 'id');
    }

    /**
     * 关联选项
     * @return \think\model\relation\HasMany
     */
    public function options()
    {
        return $this->hasMany(CategoryConditionOption::class, 'group_id', 'id')
            ->where('status', 1)
            ->order('sort', 'asc');
    }

    /**
     * 获取指定分类的状态组列表（含选项）
     * @param int $categoryId
     * @param string|null $locale
     * @return array
     */
    public static function getGroupsWithOptions(int $categoryId, ?string $locale = null): array
    {
        $locale = $locale ?? app()->getLang();

        $groups = self::where('category_id', $categoryId)
            ->where('status', 1)
            ->order('sort', 'asc')
            ->select()
            ->toArray();

        // 附加翻译
        $groups = self::appendTranslations($groups, $locale);

        // 获取每个组的选项
        $groupIds = array_column($groups, 'id');
        if ($groupIds) {
            $options = CategoryConditionOption::whereIn('group_id', $groupIds)
                ->where('status', 1)
                ->order('sort', 'asc')
                ->select()
                ->toArray();

            // 附加选项翻译
            $options = CategoryConditionOption::appendTranslations($options, $locale);

            // 按组ID分组
            $optionsByGroup = [];
            foreach ($options as $option) {
                $optionsByGroup[$option['group_id']][] = $option;
            }

            // 合并到组
            foreach ($groups as &$group) {
                $group['options'] = $optionsByGroup[$group['id']] ?? [];
            }
        }

        return $groups;
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
            'category_id' => $this->category_id,
            'name' => $this->getTranslated('name', $locale),
            'icon' => $this->icon,
            'sort' => $this->sort,
            'is_required' => $this->is_required,
            'status' => $this->status,
        ];
    }
}
