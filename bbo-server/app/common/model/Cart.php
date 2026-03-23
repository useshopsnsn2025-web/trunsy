<?php
declare(strict_types=1);

namespace app\common\model;

use think\Model;

/**
 * 购物车模型
 */
class Cart extends Model
{
    /**
     * 表名
     * @var string
     */
    protected $name = 'carts';

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
     * 关联商品
     * @return \think\model\relation\BelongsTo
     */
    public function goods()
    {
        return $this->belongsTo(Goods::class, 'goods_id', 'id');
    }

    /**
     * 关联用户
     * @return \think\model\relation\BelongsTo
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    /**
     * 获取用户购物车列表
     * @param int $userId
     * @return \think\Collection
     */
    public static function getUserCart(int $userId)
    {
        return self::where('user_id', $userId)
            ->with(['goods'])
            ->order('created_at', 'desc')
            ->select();
    }

    /**
     * 获取用户购物车数量
     * @param int $userId
     * @return int
     */
    public static function getUserCartCount(int $userId): int
    {
        return self::where('user_id', $userId)->count();
    }

    /**
     * 检查商品是否已在购物车
     * @param int $userId
     * @param int $goodsId
     * @return static|null
     */
    public static function findByUserAndGoods(int $userId, int $goodsId)
    {
        return self::where('user_id', $userId)
            ->where('goods_id', $goodsId)
            ->find();
    }
}
