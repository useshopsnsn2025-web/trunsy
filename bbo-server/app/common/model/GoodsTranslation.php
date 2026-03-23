<?php
declare(strict_types=1);

namespace app\common\model;

use think\Model;

/**
 * 商品翻译模型
 */
class GoodsTranslation extends Model
{
    /**
     * 表名
     * @var string
     */
    protected $name = 'goods_translations';

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
        'goods_id' => 'integer',
        'is_original' => 'integer',
        'is_auto_translated' => 'integer',
        'specs' => 'json',
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
