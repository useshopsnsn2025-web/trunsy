<?php
declare(strict_types=1);

namespace app\api\controller;

use think\Response;
use think\facade\Db;
use app\common\model\Cart as CartModel;
use app\common\model\Goods as GoodsModel;
use app\common\model\Promotion;
use app\common\model\PromotionGoods;

/**
 * 购物车控制器
 */
class Cart extends Base
{
    /**
     * 获取购物车列表
     * @return Response
     */
    public function index(): Response
    {
        $cartItems = CartModel::getUserCart($this->userId);

        // 收集所有商品ID用于批量查询活动信息
        $goodsIds = [];
        foreach ($cartItems as $item) {
            if ($item->goods) {
                $goodsIds[] = $item->goods_id;
            }
        }

        // 批量获取活动价格信息
        $promotionMap = $this->getBatchGoodsPromotionInfo($goodsIds);

        $list = [];
        $totalAmount = 0;
        $totalShipping = 0;
        $selectedCount = 0;

        foreach ($cartItems as $item) {
            $goods = $item->goods;
            if (!$goods) {
                continue;
            }

            // 获取活动信息
            $promotion = $promotionMap[$goods->id] ?? null;

            // 计算实际价格（优先活动价格）
            $actualPrice = $promotion ? $promotion['promotionPrice'] : (float)$goods->price;

            $cartItem = [
                'id' => $item->id,
                'goodsId' => $item->goods_id,
                'quantity' => $item->quantity,
                'selected' => (bool)$item->selected,
                'createdAt' => $item->created_at,
                'updatedAt' => $item->updated_at,
                'goods' => [
                    'id' => $goods->id,
                    'goodsNo' => $goods->goods_no,
                    'title' => $goods->getTranslated('title', $this->locale),
                    'price' => (float)$goods->price,
                    'originalPrice' => $goods->original_price ? (float)$goods->original_price : null,
                    'currency' => $goods->currency,
                    'images' => $goods->images,
                    'stock' => $goods->stock,
                    'status' => $goods->status,
                    'freeShipping' => (bool)$goods->free_shipping,
                    'shippingFee' => (float)($goods->shipping_fee ?? 0),
                    'condition' => $goods->condition,
                ],
            ];

            // 添加活动信息
            if ($promotion) {
                $cartItem['goods']['promotion'] = $promotion;
            }

            $list[] = $cartItem;

            // 计算已选商品的总额（使用实际价格）
            if ($item->selected) {
                $selectedCount++;
                $totalAmount += $actualPrice * $item->quantity;
                if (!$goods->free_shipping) {
                    $totalShipping += $goods->shipping_fee ?? 0;
                }
            }
        }

        return $this->success([
            'list' => $list,
            'total' => count($list),
            'selectedCount' => $selectedCount,
            'totalAmount' => $totalAmount,
            'totalShipping' => $totalShipping,
        ]);
    }

    /**
     * 添加到购物车
     * @return Response
     */
    public function create(): Response
    {
        $goodsId = (int)input('post.goodsId');
        $quantity = (int)input('post.quantity', 1);

        if (!$goodsId) {
            return $this->error('Goods ID required');
        }

        if ($quantity < 1) {
            $quantity = 1;
        }

        // 检查商品是否存在且在售
        $goods = GoodsModel::where('id', $goodsId)
            ->where('status', GoodsModel::STATUS_ON_SALE)
            ->find();

        if (!$goods) {
            return $this->error('Goods not found', 404);
        }

        // 检查库存
        if ($goods->stock < $quantity) {
            return $this->error('Insufficient stock');
        }

        // 检查是否已在购物车
        $existingItem = CartModel::findByUserAndGoods($this->userId, $goodsId);

        Db::startTrans();
        try {
            if ($existingItem) {
                // 已存在，更新数量
                $newQuantity = $existingItem->quantity + $quantity;
                if ($newQuantity > $goods->stock) {
                    $newQuantity = $goods->stock;
                }
                $existingItem->quantity = $newQuantity;
                $existingItem->save();
                $cartItem = $existingItem;
            } else {
                // 新增购物车项
                $cartItem = new CartModel();
                $cartItem->user_id = $this->userId;
                $cartItem->goods_id = $goodsId;
                $cartItem->quantity = min($quantity, $goods->stock);
                $cartItem->selected = 1;
                $cartItem->save();
            }

            Db::commit();

            return $this->success([
                'id' => $cartItem->id,
                'goodsId' => $cartItem->goods_id,
                'quantity' => $cartItem->quantity,
                'selected' => (bool)$cartItem->selected,
            ], 'Cart item added');

        } catch (\Exception $e) {
            Db::rollback();
            return $this->error('Add to cart failed');
        }
    }

    /**
     * 更新购物车项
     * @param int $id
     * @return Response
     */
    public function update(int $id): Response
    {
        $cartItem = CartModel::where('id', $id)
            ->where('user_id', $this->userId)
            ->find();

        if (!$cartItem) {
            return $this->error('Cart item not found', 404);
        }

        $quantity = input('post.quantity');
        $selected = input('post.selected');

        // 更新数量
        if ($quantity !== null) {
            $quantity = (int)$quantity;
            if ($quantity < 1) {
                return $this->error('Quantity must be at least 1');
            }

            // 检查库存
            $goods = GoodsModel::find($cartItem->goods_id);
            if ($goods && $quantity > $goods->stock) {
                return $this->error('Insufficient stock');
            }

            $cartItem->quantity = $quantity;
        }

        // 更新选中状态
        if ($selected !== null) {
            $cartItem->selected = $selected ? 1 : 0;
        }

        try {
            $cartItem->save();
            return $this->success([
                'id' => $cartItem->id,
                'goodsId' => $cartItem->goods_id,
                'quantity' => $cartItem->quantity,
                'selected' => (bool)$cartItem->selected,
            ], 'Cart item updated');
        } catch (\Exception $e) {
            return $this->error('Update cart failed');
        }
    }

    /**
     * 从购物车移除
     * @param int $id
     * @return Response
     */
    public function delete(int $id): Response
    {
        $cartItem = CartModel::where('id', $id)
            ->where('user_id', $this->userId)
            ->find();

        if (!$cartItem) {
            return $this->error('Cart item not found', 404);
        }

        try {
            $cartItem->delete();
            return $this->success([], 'Cart item removed');
        } catch (\Exception $e) {
            return $this->error('Remove from cart failed');
        }
    }

    /**
     * 清空购物车
     * @return Response
     */
    public function clear(): Response
    {
        try {
            CartModel::where('user_id', $this->userId)->delete();
            return $this->success([], 'Cart cleared');
        } catch (\Exception $e) {
            return $this->error('Clear cart failed');
        }
    }

    /**
     * 获取购物车数量
     * @return Response
     */
    public function count(): Response
    {
        $count = CartModel::getUserCartCount($this->userId);
        return $this->success(['count' => $count]);
    }

    /**
     * 批量更新选中状态
     * @return Response
     */
    public function selection(): Response
    {
        $ids = input('post.ids', []);
        $selected = input('post.selected', true);

        if (empty($ids) || !is_array($ids)) {
            return $this->error('Please select items');
        }

        try {
            CartModel::where('user_id', $this->userId)
                ->whereIn('id', $ids)
                ->update(['selected' => $selected ? 1 : 0]);

            return $this->success(['success' => true], 'Selection updated');
        } catch (\Exception $e) {
            return $this->error('Update selection failed');
        }
    }

    /**
     * 全选/取消全选
     * @return Response
     */
    public function selectAll(): Response
    {
        $selected = input('post.selected', true);

        try {
            CartModel::where('user_id', $this->userId)
                ->update(['selected' => $selected ? 1 : 0]);

            return $this->success(['success' => true], 'Selection updated');
        } catch (\Exception $e) {
            return $this->error('Update selection failed');
        }
    }

    /**
     * 批量获取商品活动价格信息
     * @param array $goodsIds
     * @return array
     */
    protected function getBatchGoodsPromotionInfo(array $goodsIds): array
    {
        if (empty($goodsIds)) {
            return [];
        }

        $now = date('Y-m-d H:i:s');

        // 查找这些商品参与的进行中的活动
        $promotionGoodsList = PromotionGoods::alias('pg')
            ->join('promotions p', 'pg.promotion_id = p.id')
            ->whereIn('pg.goods_id', $goodsIds)
            ->where('p.status', Promotion::STATUS_RUNNING)
            ->where('p.start_time', '<=', $now)
            ->where('p.end_time', '>=', $now)
            ->field('pg.*, p.type as promotion_type, p.start_time, p.end_time, p.id as promotion_id')
            ->select();

        if ($promotionGoodsList->isEmpty()) {
            return [];
        }

        // 获取所有相关活动的翻译名称
        $promotionIds = array_unique($promotionGoodsList->column('promotion_id'));
        $promotions = Promotion::whereIn('id', $promotionIds)->select()->column(null, 'id');

        $result = [];
        foreach ($promotionGoodsList as $pg) {
            $promotion = $promotions[$pg['promotion_id']] ?? null;
            $promotionName = $promotion ? $promotion->getTranslated('name', $this->locale) : '';

            $result[$pg['goods_id']] = [
                'id' => (int) $pg['promotion_id'],
                'name' => $promotionName,
                'type' => (int) $pg['promotion_type'],
                'promotionPrice' => (float) $pg['promotion_price'],
                'discount' => (float) $pg['discount'],
                'discountPercent' => round((1 - (float) $pg['discount']) * 100),
                'endTime' => $pg['end_time'],
            ];
        }

        return $result;
    }
}
