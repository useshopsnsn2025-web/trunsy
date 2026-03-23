<?php
declare(strict_types=1);

namespace app\common\model;

use think\Model;

/**
 * 浏览历史模型
 */
class BrowseHistory extends Model
{
    /**
     * 表名
     * @var string
     */
    protected $name = 'browse_histories';

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
     * @var bool
     */
    protected $updateTime = false;

    /**
     * 类型转换
     * @var array
     */
    protected $type = [
        'view_count' => 'integer',
    ];

    /**
     * 关联商品
     * @return \think\model\relation\BelongsTo
     */
    public function goods()
    {
        return $this->belongsTo(Goods::class, 'goods_id', 'id');
    }
}
