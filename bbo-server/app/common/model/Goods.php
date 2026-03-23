<?php
declare(strict_types=1);

namespace app\common\model;

use think\Model;
use app\common\traits\Translatable;
use app\common\model\CategoryAttribute;
use app\common\model\CategoryAttributeTranslation;
use app\common\model\AttributeOptionTranslation;
use app\common\helper\UrlHelper;

/**
 * 商品模型
 */
class Goods extends Model
{
    use Translatable;

    /**
     * 表名
     * @var string
     */
    protected $name = 'goods';

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
     * 软删除字段
     * @var string
     */
    protected $deleteTime = 'deleted_at';

    /**
     * 翻译模型
     * @var string
     */
    protected $translationModel = GoodsTranslation::class;

    /**
     * 可翻译字段
     * @var array
     */
    protected $translatable = ['title', 'description', 'specs'];

    /**
     * JSON 字段
     * @var array
     */
    protected $json = ['images'];

    /**
     * 状态常量
     */
    const STATUS_PENDING = 0;    // 待审核
    const STATUS_ON_SALE = 1;    // 上架
    const STATUS_OFF_SHELF = 2;  // 下架
    const STATUS_SOLD_OUT = 3;   // 售罄
    const STATUS_REJECTED = 4;   // 违规下架

    /**
     * 类型常量
     */
    const TYPE_C2C = 1;  // 个人闲置
    const TYPE_B2C = 2;  // 商家商品

    /**
     * 关联用户
     * @return \think\model\relation\BelongsTo
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id')
            ->field('id,uuid,nickname,avatar,is_verified');
    }

    /**
     * 关联店铺
     * @return \think\model\relation\BelongsTo
     */
    public function shop()
    {
        return $this->belongsTo(Shop::class, 'shop_id', 'id')
            ->field('id,shop_no,logo,rating');
    }

    /**
     * 关联分类
     * @return \think\model\relation\BelongsTo
     */
    public function category()
    {
        return $this->belongsTo(Category::class, 'category_id', 'id');
    }

    /**
     * 获取发货地国家
     * 优先使用系统配置的发货地，如果没有设置则使用商品自己的location_country
     * @param string|null $locale 语言代码
     * @return string|null
     */
    public function getLocationCountry(?string $locale = null): ?string
    {
        // 获取系统配置的发货地（支持多语言）
        if ($locale) {
            $configCountry = SystemConfig::getConfigTranslated('Place_of_shipment', $locale);
        } else {
            $configCountry = SystemConfig::getConfig('Place_of_shipment');
        }
        if (!empty($configCountry)) {
            return $configCountry;
        }
        // 如果没有设置系统配置，返回商品自己的发货地
        return $this->location_country;
    }

    /**
     * 增加浏览量
     * @return bool
     */
    public function incrementViews(): bool
    {
        $this->views = $this->views + 1;
        return $this->save();
    }

    /**
     * 增加收藏数
     * @param int $num
     * @return bool
     */
    public function incrementLikes(int $num = 1): bool
    {
        $this->likes = $this->likes + $num;
        return $this->save();
    }

    /**
     * 减少库存
     * @param int $quantity
     * @return bool
     */
    public function decrementStock(int $quantity = 1): bool
    {
        if ($this->stock < $quantity) {
            return false;
        }

        $this->stock = $this->stock - $quantity;
        $this->sold_count = $this->sold_count + $quantity;

        // 库存为0时下架
        if ($this->stock === 0) {
            $this->status = self::STATUS_SOLD_OUT;
        }

        return $this->save();
    }

    /**
     * 转换为 API 数组
     * @param string|null $locale
     * @return array
     */
    public function toApiArray(?string $locale = null): array
    {
        // 转换图片 URL
        $images = array_values((array) $this->images);
        $images = UrlHelper::convertImageUrls($images);

        // 转换视频 URL
        $video = UrlHelper::convertImageUrl($this->video);

        $data = [
            'id' => $this->id,
            'goodsNo' => $this->goods_no,
            'userId' => $this->user_id,
            'shopId' => $this->shop_id,
            'categoryId' => $this->category_id,
            'type' => (int) $this->getData('type'),  // 使用 getData 避免与 $type 属性冲突
            'condition' => $this->condition,
            'price' => (float) $this->price,
            'originalPrice' => $this->original_price ? (float) $this->original_price : null,
            'currency' => $this->currency,
            'stock' => (int) $this->stock,
            'soldCount' => (int) $this->sold_count,
            'images' => $images,
            'video' => $video,
            'locationCountry' => $this->getLocationCountry($locale),
            'locationCity' => $this->location_city,
            'shippingFee' => (float) $this->shipping_fee,
            'tax' => (float) ($this->tax ?? 0),
            'freeShipping' => $this->free_shipping == 1,
            'views' => (int) $this->views,
            'likes' => (int) $this->likes,
            'isNegotiable' => $this->is_negotiable == 1,
            'status' => (int) $this->status,
            'createdAt' => $this->created_at,
            'updatedAt' => $this->updated_at,
        ];

        // 获取翻译
        $data['title'] = $this->getTranslated('title', $locale);
        $data['description'] = $this->getTranslated('description', $locale);

        // 获取商品规格属性
        $data['specs'] = $this->getTranslatedSpecs($locale);

        return $data;
    }

    /**
     * 获取翻译后的商品规格属性（带降级逻辑）
     * 降级顺序：请求语言 → en-us → 原始值
     * @param string|null $locale
     * @return array
     */
    public function getTranslatedSpecs(?string $locale = null): array
    {
        $locale = $locale ?? 'en-us';

        // 尝试获取请求语言的翻译
        $translation = $this->translation($locale);

        // 降级到 en-us
        if ((!$translation || empty($translation->specs)) && $locale !== 'en-us') {
            $translation = $this->translation('en-us');
        }

        if (!$translation || empty($translation->specs)) {
            return [];
        }

        $specs = is_string($translation->specs) ? json_decode($translation->specs, true) : (array) $translation->specs;
        if (empty($specs)) {
            return [];
        }

        // 获取分类的属性定义（包含全局属性 category_id=0）
        $categoryId = $this->category_id;
        $attributes = CategoryAttribute::where(function($query) use ($categoryId) {
                $query->where('category_id', $categoryId)
                      ->whereOr('category_id', 0);
            })
            ->order('sort', 'desc')
            ->select();

        if ($attributes->isEmpty()) {
            return [];
        }

        // 获取属性翻译（带降级）
        $attrIds = $attributes->column('id');
        $attrTranslations = CategoryAttributeTranslation::where('attribute_id', 'in', $attrIds)
            ->where('locale', $locale)
            ->column('name', 'attribute_id');

        // 降级查询 en-us 属性翻译
        $attrFallbackTranslations = [];
        if ($locale !== 'en-us') {
            $attrFallbackTranslations = CategoryAttributeTranslation::where('attribute_id', 'in', $attrIds)
                ->where('locale', 'en-us')
                ->column('name', 'attribute_id');
        }

        // 获取选项翻译（带降级）
        $optionTranslations = AttributeOptionTranslation::alias('aot')
            ->join('attribute_options ao', 'aot.option_id = ao.id')
            ->where('ao.attribute_id', 'in', $attrIds)
            ->where('aot.locale', $locale)
            ->column('aot.label', 'ao.option_value');

        // 降级查询 en-us 选项翻译
        $optionFallbackTranslations = [];
        if ($locale !== 'en-us') {
            $optionFallbackTranslations = AttributeOptionTranslation::alias('aot')
                ->join('attribute_options ao', 'aot.option_id = ao.id')
                ->where('ao.attribute_id', 'in', $attrIds)
                ->where('aot.locale', 'en-us')
                ->column('aot.label', 'ao.option_value');
        }

        $result = [];
        foreach ($attributes as $attr) {
            $key = $attr->attr_key;
            if (!isset($specs[$key]) || $specs[$key] === '' || $specs[$key] === null) {
                continue;
            }

            $value = $specs[$key];
            // 属性名称降级
            $label = $attrTranslations[$attr->id] ?? $attrFallbackTranslations[$attr->id] ?? $key;

            // 处理多选值（数组）
            if (is_array($value)) {
                $translatedValues = [];
                foreach ($value as $v) {
                    // 选项值降级
                    $translatedValues[] = $optionTranslations[$v] ?? $optionFallbackTranslations[$v] ?? $v;
                }
                $displayValue = implode(', ', $translatedValues);
            } else {
                // 单选值降级
                $displayValue = $optionTranslations[$value] ?? $optionFallbackTranslations[$value] ?? $value;
            }

            $result[] = [
                'key' => $key,
                'label' => $label,
                'value' => $displayValue,
            ];
        }

        return $result;
    }

    /**
     * 获取第一张图片
     * @return string
     */
    public function getFirstImage(): string
    {
        $images = $this->images;

        // 如果是 stdClass 对象，转换为数组
        if ($images instanceof \stdClass) {
            $images = (array) $images;
        }

        if (is_array($images) && !empty($images)) {
            // 使用 array_values 转换为索引数组，然后获取第一个
            $indexed = array_values($images);
            return $indexed[0] ?? '';
        }
        return '';
    }
}
