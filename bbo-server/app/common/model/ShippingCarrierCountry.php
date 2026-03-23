<?php
declare(strict_types=1);

namespace app\common\model;

use think\Model;

/**
 * 物流运输商国家配置模型
 */
class ShippingCarrierCountry extends Model
{
    protected $name = 'shipping_carrier_countries';
    protected $pk = 'id';
    protected $autoWriteTimestamp = 'datetime';
    protected $createTime = 'created_at';
    protected $updateTime = 'updated_at';

    protected $type = [
        'id' => 'integer',
        'carrier_id' => 'integer',
        'shipping_fee' => 'float',
        'free_shipping_threshold' => 'float',
        'estimated_days_min' => 'integer',
        'estimated_days_max' => 'integer',
        'is_enabled' => 'integer',
    ];

    /**
     * 关联运输商
     */
    public function carrier()
    {
        return $this->belongsTo(ShippingCarrier::class, 'carrier_id', 'id');
    }

    /**
     * 获取运输商在指定国家的配置
     */
    public static function getConfig(int $carrierId, string $countryCode): ?self
    {
        return self::where('carrier_id', $carrierId)
            ->where('country_code', $countryCode)
            ->where('is_enabled', 1)
            ->find();
    }

    /**
     * 获取某个运输商的所有国家配置
     */
    public static function getCarrierCountries(int $carrierId): array
    {
        return self::where('carrier_id', $carrierId)
            ->order('country_code', 'asc')
            ->select()
            ->toArray();
    }

    /**
     * 批量保存国家配置
     */
    public static function saveCountries(int $carrierId, array $countries): void
    {
        // 删除旧配置
        self::where('carrier_id', $carrierId)->delete();

        // 插入新配置
        foreach ($countries as $country) {
            self::create([
                'carrier_id' => $carrierId,
                'country_code' => $country['country_code'],
                'shipping_fee' => $country['shipping_fee'] ?? 0,
                'currency' => $country['currency'] ?? 'USD',
                'free_shipping_threshold' => $country['free_shipping_threshold'] ?? null,
                'estimated_days_min' => $country['estimated_days_min'] ?? null,
                'estimated_days_max' => $country['estimated_days_max'] ?? null,
                'is_enabled' => $country['is_enabled'] ?? 1,
            ]);
        }
    }
}
