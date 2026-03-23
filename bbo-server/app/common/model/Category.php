<?php
declare(strict_types=1);

namespace app\common\model;

use think\Model;
use app\common\traits\Translatable;
use app\common\helper\UrlHelper;

/**
 * 分类模型
 */
class Category extends Model
{
    use Translatable;

    /**
     * 表名
     * @var string
     */
    protected $name = 'categories';

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
    protected $translationModel = CategoryTranslation::class;

    /**
     * 可翻译字段
     * @var array
     */
    protected $translatable = ['name', 'description'];

    /**
     * 类型转换
     * @var array
     */
    protected $type = [
        'id' => 'integer',
        'parent_id' => 'integer',
        'sort' => 'integer',
        'level' => 'integer',
        'status' => 'integer',
        'is_hot' => 'integer',
    ];

    /**
     * 获取子分类
     * @return \think\model\relation\HasMany
     */
    public function children()
    {
        return $this->hasMany(Category::class, 'parent_id', 'id')
            ->where('status', 1)
            ->order('sort', 'desc');
    }

    /**
     * 获取父分类
     * @return \think\model\relation\BelongsTo
     */
    public function parent()
    {
        return $this->belongsTo(Category::class, 'parent_id', 'id');
    }

    /**
     * 获取分类树
     * @param string|null $locale
     * @return array
     */
    public static function getTree(?string $locale = null): array
    {
        $locale = $locale ?? app()->getLang();

        $categories = self::where('status', 1)
            ->where('parent_id', 0)
            ->order('sort', 'desc')
            ->select()
            ->toArray();

        // 附加翻译
        $categories = self::appendTranslations($categories, $locale);

        // 转换图片 URL
        $categories = UrlHelper::convertListFieldUrls($categories, ['icon', 'image']);

        // 获取子分类
        foreach ($categories as &$category) {
            $children = self::where('status', 1)
                ->where('parent_id', $category['id'])
                ->order('sort', 'desc')
                ->select()
                ->toArray();

            $children = self::appendTranslations($children, $locale);
            // 转换子分类图片 URL
            $category['children'] = UrlHelper::convertListFieldUrls($children, ['icon', 'image']);
        }

        return $categories;
    }

    /**
     * 获取热门分类
     * @param string|null $locale
     * @return array
     */
    public static function getHotCategories(?string $locale = null): array
    {
        $locale = $locale ?? app()->getLang();

        $categories = self::where('status', 1)
            ->where('is_hot', 1)
            ->order('sort', 'desc')
            ->select()
            ->toArray();

        // 附加翻译
        $categories = self::appendTranslations($categories, $locale);

        // 转换图片 URL
        $categories = UrlHelper::convertListFieldUrls($categories, ['icon', 'image']);

        // 获取每个分类的商品数量（只统计上架状态的商品）
        $categoryIds = array_column($categories, 'id');
        if ($categoryIds) {
            $goodsCounts = Goods::whereIn('category_id', $categoryIds)
                ->where('status', Goods::STATUS_ON_SALE)
                ->group('category_id')
                ->column('count(*)', 'category_id');

            foreach ($categories as &$category) {
                $category['goods_count'] = $goodsCounts[$category['id']] ?? 0;
            }
        }

        return $categories;
    }
}
