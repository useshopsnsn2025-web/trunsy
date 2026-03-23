<?php
declare(strict_types=1);

namespace app\common\model;

use think\Model;

/**
 * 快捷回复翻译模型
 */
class QuickReplyTranslation extends Model
{
    /**
     * 表名
     * @var string
     */
    protected $name = 'quick_reply_translations';

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
        'reply_id' => 'integer',
    ];

    /**
     * 关联快捷回复
     * @return \think\model\relation\BelongsTo
     */
    public function reply()
    {
        return $this->belongsTo(QuickReply::class, 'reply_id', 'id');
    }
}
