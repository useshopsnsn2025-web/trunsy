<?php

namespace app\common\model;

use think\Model;
use app\common\traits\Translatable;

/**
 * 出售常见问题模型
 */
class SellFaq extends Model
{
    use Translatable;

    protected $name = 'sell_faqs';
    protected $pk = 'id';

    // 翻译相关配置
    protected $translationModel = SellFaqTranslation::class;
    protected $translationForeignKey = 'faq_id';
    protected $translatable = ['question', 'answer'];

    // 自动时间戳
    protected $autoWriteTimestamp = 'datetime';
    protected $createTime = 'created_at';
    protected $updateTime = 'updated_at';

    /**
     * 获取翻译关联
     */
    public function translations()
    {
        return $this->hasMany(SellFaqTranslation::class, 'faq_id', 'id');
    }

    /**
     * 获取启用的FAQ列表（带翻译）
     */
    public static function getEnabledList(string $locale): array
    {
        $list = self::where('status', 1)
            ->order('sort_order', 'desc')
            ->order('id', 'asc')
            ->select()
            ->toArray();

        return self::appendTranslations($list, $locale);
    }

    /**
     * 转换为 API 数组
     */
    public function toApiArray(string $locale): array
    {
        return [
            'id' => $this->id,
            'question' => $this->getTranslated('question', $locale),
            'answer' => $this->getTranslated('answer', $locale),
            'sort_order' => $this->sort_order,
            'status' => $this->status,
        ];
    }
}
