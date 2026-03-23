<?php
declare(strict_types=1);

namespace app\common\model;

use think\Model;

/**
 * 活动商品关联模型
 */
class PromotionGoods extends Model
{
    protected $name = 'promotion_goods';
    protected $pk = 'id';
    protected $autoWriteTimestamp = 'datetime';
    protected $createTime = 'created_at';
    protected $updateTime = false;

    protected $type = [
        'id' => 'integer',
        'promotion_id' => 'integer',
        'goods_id' => 'integer',
        'promotion_price' => 'float',
        'discount' => 'float',
        'stock' => 'integer',
        'sold_count' => 'integer',
        'limit_per_user' => 'integer',
    ];

    /**
     * 关联活动
     */
    public function promotion()
    {
        return $this->belongsTo(Promotion::class, 'promotion_id', 'id');
    }

    /**
     * 关联商品
     */
    public function goods()
    {
        return $this->belongsTo(Goods::class, 'goods_id', 'id');
    }

    /**
     * 转换为 API 数组
     */
    public function toApiArray(?string $locale = null): array
    {
        $goods = $this->goods;

        return [
            'id' => $this->id,
            'goodsId' => $this->goods_id,
            'promotionPrice' => (float) $this->promotion_price,
            'discount' => (float) $this->discount,
            'stock' => (int) $this->stock,
            'soldCount' => (int) $this->sold_count,
            'limitPerUser' => (int) $this->limit_per_user,
            'goods' => $goods ? [
                'id' => $goods->id,
                'goodsNo' => $goods->goods_no,
                'title' => $goods->getTranslated('title', $locale),
                'price' => (float) $goods->price,
                'originalPrice' => $goods->original_price ? (float) $goods->original_price : null,
                'currency' => $goods->currency,
                'images' => array_values((array) $goods->images),
                'condition' => (int) $goods->condition,
                'freeShipping' => $goods->free_shipping == 1,
                'stock' => (int) $goods->stock,
                'likes' => (int) $goods->likes,
            ] : null,
        ];
    }
}
