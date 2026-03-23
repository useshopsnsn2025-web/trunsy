<?php
declare(strict_types=1);

namespace app\common\model;

use think\Model;
use app\common\helper\UrlHelper;

/**
 * 商品草稿模型
 */
class GoodsDraft extends Model
{
    /**
     * 表名
     * @var string
     */
    protected $name = 'goods_drafts';

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
     * JSON 字段
     * @var array
     */
    protected $json = ['images', 'specs', 'condition_values'];

    /**
     * 类型转换
     * @var array
     */
    protected $type = [
        'price' => 'float',
        'shipping_fee' => 'float',
        'is_negotiable' => 'boolean',
        'free_shipping' => 'boolean',
    ];

    /**
     * 关联用户
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * 关联分类
     */
    public function category()
    {
        return $this->belongsTo(Category::class, 'category_id');
    }

    /**
     * 保存或更新草稿
     * @param int $userId
     * @param array $data
     * @return GoodsDraft
     */
    public static function saveDraft(int $userId, array $data): GoodsDraft
    {
        // 查找用户现有草稿
        $draft = self::where('user_id', $userId)->find();

        if (!$draft) {
            $draft = new self();
            $draft->user_id = $userId;
        }

        // 更新字段
        $fields = [
            'category_id', 'title', 'description', 'images', 'price',
            'condition_level', 'is_negotiable', 'free_shipping',
            'shipping_fee', 'specs', 'condition_values', 'current_step'
        ];

        foreach ($fields as $field) {
            if (array_key_exists($field, $data)) {
                $draft->$field = $data[$field];
            }
        }

        $draft->save();
        return $draft;
    }

    /**
     * 获取用户草稿
     * @param int $userId
     * @return GoodsDraft|null
     */
    public static function getUserDraft(int $userId): ?GoodsDraft
    {
        return self::where('user_id', $userId)->find();
    }

    /**
     * 删除用户草稿
     * @param int $userId
     * @return bool
     */
    public static function deleteDraft(int $userId): bool
    {
        return self::where('user_id', $userId)->delete() > 0;
    }

    /**
     * 转换为 API 数组
     * @param string|null $locale
     * @return array
     */
    public function toApiArray(?string $locale = null): array
    {
        $data = [
            'id' => $this->id,
            'category_id' => $this->category_id,
            'title' => $this->title,
            'description' => $this->description,
            'images' => $this->images ? array_values((array) $this->images) : [],
            'price' => $this->price,
            'condition_level' => $this->condition_level,
            'is_negotiable' => $this->is_negotiable,
            'free_shipping' => $this->free_shipping,
            'shipping_fee' => $this->shipping_fee,
            'specs' => $this->specs ?: [],
            'condition_values' => $this->condition_values ?: [],
            'current_step' => $this->current_step,
            'updated_at' => $this->updated_at,
        ];

        // 如果有分类，获取分类名称
        if ($this->category_id) {
            $category = Category::find($this->category_id);
            if ($category) {
                $data['category_name'] = $category->getTranslated('name', $locale);
            }
        }

        return $data;
    }
}
