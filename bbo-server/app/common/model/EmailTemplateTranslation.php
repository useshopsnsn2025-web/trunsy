<?php
declare(strict_types=1);

namespace app\common\model;

use think\Model;

/**
 * 邮件模板翻译模型
 */
class EmailTemplateTranslation extends Model
{
    /**
     * 表名
     * @var string
     */
    protected $name = 'email_template_translations';

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
     * 类型转换
     * @var array
     */
    protected $type = [
        'id' => 'integer',
        'template_id' => 'integer',
    ];

    /**
     * 关联模板
     */
    public function template()
    {
        return $this->belongsTo(EmailTemplate::class, 'template_id');
    }
}
