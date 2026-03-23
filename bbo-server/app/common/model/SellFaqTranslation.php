<?php

namespace app\common\model;

use think\Model;

/**
 * 出售常见问题翻译模型
 */
class SellFaqTranslation extends Model
{
    protected $name = 'sell_faq_translations';
    protected $pk = 'id';

    // 自动时间戳
    protected $autoWriteTimestamp = 'datetime';
    protected $createTime = 'created_at';
    protected $updateTime = 'updated_at';

    /**
     * 关联FAQ
     */
    public function faq()
    {
        return $this->belongsTo(SellFaq::class, 'faq_id', 'id');
    }
}
