<?php
declare(strict_types=1);

namespace app\common\model;

use think\Model;

/**
 * 客服人员翻译模型
 */
class CustomerServiceTranslation extends Model
{
    /**
     * 表名
     * @var string
     */
    protected $name = 'customer_service_translations';

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
        'service_id' => 'integer',
    ];

    /**
     * 关联客服
     * @return \think\model\relation\BelongsTo
     */
    public function service()
    {
        return $this->belongsTo(CustomerService::class, 'service_id', 'id');
    }
}
