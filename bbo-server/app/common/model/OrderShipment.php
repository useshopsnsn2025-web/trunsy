<?php
declare(strict_types=1);

namespace app\common\model;

use think\Model;

/**
 * 订单物流模型
 */
class OrderShipment extends Model
{
    /**
     * 表名
     * @var string
     */
    protected $name = 'order_shipments';

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
        'order_id' => 'integer',
        'carrier_id' => 'integer',
        'shipping_status' => 'integer',
        'tracking_info' => 'json',
        'carrier_snapshot' => 'json',
    ];

    /**
     * 物流状态常量
     */
    const STATUS_PENDING = 0;    // 待揽收
    const STATUS_IN_TRANSIT = 1; // 运输中
    const STATUS_DELIVERED = 2;  // 已签收

    /**
     * 状态文本
     */
    const STATUS_TEXT = [
        0 => '待揽收',
        1 => '运输中',
        2 => '已签收',
    ];

    /**
     * 关联订单
     * @return \think\model\relation\BelongsTo
     */
    public function order()
    {
        return $this->belongsTo(Order::class, 'order_id', 'id');
    }

    /**
     * 关联运输商
     * @return \think\model\relation\BelongsTo
     */
    public function carrier()
    {
        return $this->belongsTo(ShippingCarrier::class, 'carrier_id', 'id');
    }

    /**
     * 生成追踪URL
     * @return string|null
     */
    public function getTrackingUrl(): ?string
    {
        if (empty($this->shipping_no)) {
            return null;
        }

        $carrierSnapshot = $this->carrier_snapshot;
        if (!empty($carrierSnapshot['tracking_url'])) {
            return str_replace('{tracking_number}', $this->shipping_no, $carrierSnapshot['tracking_url']);
        }

        return null;
    }

    /**
     * 转换为API数组
     * @param string $locale
     * @return array
     */
    public function toApiArray(string $locale = 'en-us'): array
    {
        $carrierSnapshot = $this->carrier_snapshot ?? [];

        return [
            'id' => $this->id,
            'order_id' => $this->order_id,
            'shipping_company' => $this->shipping_company,
            'shipping_no' => $this->shipping_no,
            'shipping_status' => $this->shipping_status,
            'shipping_status_text' => self::STATUS_TEXT[$this->shipping_status] ?? 'Unknown',
            'carrier' => [
                'id' => $carrierSnapshot['id'] ?? null,
                'code' => $carrierSnapshot['code'] ?? null,
                'name' => $carrierSnapshot['name'] ?? $this->shipping_company,
                'logo' => $carrierSnapshot['logo'] ?? null,
            ],
            'tracking_url' => $this->getTrackingUrl(),
            'tracking_info' => $this->tracking_info,
            'shipped_at' => $this->shipped_at,
            'received_at' => $this->received_at,
            'created_at' => $this->created_at,
        ];
    }
}
