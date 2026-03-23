<?php
declare(strict_types=1);

namespace app\common\model;

use think\Model;

/**
 * 系统配置翻译模型
 */
class SystemConfigTranslation extends Model
{
    /**
     * 表名
     * @var string
     */
    protected $name = 'system_config_translations';

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
        'config_id' => 'integer',
        'is_original' => 'boolean',
        'is_auto_translated' => 'boolean',
    ];

    /**
     * 关联配置
     */
    public function config()
    {
        return $this->belongsTo(SystemConfig::class, 'config_id', 'id');
    }
}
