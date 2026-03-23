<?php
declare(strict_types=1);

namespace app\common\model;

use think\Model;
use app\common\traits\Translatable;

/**
 * 品类属性模型
 */
class CategoryAttribute extends Model
{
    use Translatable;

    /**
     * 表名
     * @var string
     */
    protected $name = 'category_attributes';

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
    protected $translationModel = CategoryAttributeTranslation::class;

    /**
     * 可翻译字段
     * @var array
     */
    protected $translatable = ['name', 'placeholder'];

    /**
     * 获取外键名（覆盖trait方法）
     * @return string
     */
    protected function getTranslationForeignKey(): string
    {
        return 'attribute_id';
    }

    /**
     * 类型转换
     * @var array
     */
    protected $type = [
        'id' => 'integer',
        'category_id' => 'integer',
        'is_required' => 'integer',
        'sort' => 'integer',
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
        return $this->hasMany(AttributeOption::class, 'attribute_id', 'id')
            ->where('status', 1)
            ->order('sort', 'desc');
    }

    /**
     * 获取分类的属性列表（带翻译和选项）
     * 包含全局属性（category_id = 0）和分类特定属性
     * @param int $categoryId
     * @param string|null $locale
     * @return array
     */
    public static function getAttributesByCategoryId(int $categoryId, ?string $locale = null): array
    {
        $locale = $locale ?? app()->getLang();

        // 获取全局属性（category_id = 0）和分类特定属性
        $attributes = self::where(function($query) use ($categoryId) {
                $query->where('category_id', $categoryId)
                      ->whereOr('category_id', 0);
            })
            ->where('status', 1)
            ->order('sort', 'desc')
            ->select()
            ->toArray();

        if (empty($attributes)) {
            return [];
        }

        // 附加翻译
        $attributes = self::appendTranslations($attributes, $locale);

        // 获取属性ID列表
        $attributeIds = array_column($attributes, 'id');

        // 获取所有选项
        $options = AttributeOption::where('attribute_id', 'in', $attributeIds)
            ->where('status', 1)
            ->order('sort', 'desc')
            ->select()
            ->toArray();

        // 附加选项翻译
        $options = AttributeOption::appendTranslations($options, $locale);

        // 按属性ID分组选项，同时保留parent_value和image
        $optionsByAttr = [];
        foreach ($options as $option) {
            $optionsByAttr[$option['attribute_id']][] = [
                'value' => $option['option_value'],
                'label' => $option['label'] ?? $option['option_value'],
                'image' => $option['image'] ?? null,
                'parent_value' => $option['parent_value'] ?? null,
            ];
        }

        // 组装结果
        $result = [];
        foreach ($attributes as $attr) {
            $result[] = [
                'key' => $attr['attr_key'],
                'name' => $attr['name'] ?? $attr['attr_key'],
                'placeholder' => $attr['placeholder'] ?? '',
                'input_type' => $attr['input_type'],
                'is_required' => (bool)$attr['is_required'],
                'parent_key' => $attr['parent_key'] ?? null,
                'options' => $optionsByAttr[$attr['id']] ?? [],
            ];
        }

        return $result;
    }
}
