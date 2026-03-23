<?php
declare(strict_types=1);

namespace app\common\model;

use think\Model;
use app\common\traits\Translatable;
use app\common\helper\UrlHelper;

/**
 * 物流运输商模型
 */
class ShippingCarrier extends Model
{
    use Translatable;

    protected $name = 'shipping_carriers';
    protected $pk = 'id';
    protected $autoWriteTimestamp = 'datetime';
    protected $createTime = 'created_at';
    protected $updateTime = 'updated_at';

    /**
     * 翻译模型
     */
    protected $translationModel = ShippingCarrierTranslation::class;

    /**
     * 翻译外键
     */
    protected $translationForeignKey = 'carrier_id';

    /**
     * 可翻译字段
     */
    protected $translatable = ['name', 'description'];

    protected $type = [
        'id' => 'integer',
        'estimated_days_min' => 'integer',
        'estimated_days_max' => 'integer',
        'sort' => 'integer',
        'status' => 'integer',
    ];

    // 状态常量
    const STATUS_DISABLED = 0;
    const STATUS_ENABLED = 1;

    public static function getStatusNames(): array
    {
        return [
            self::STATUS_DISABLED => '禁用',
            self::STATUS_ENABLED => '启用',
        ];
    }

    /**
     * 关联国家配置
     */
    public function countries()
    {
        return $this->hasMany(ShippingCarrierCountry::class, 'carrier_id');
    }

    /**
     * 关联翻译
     */
    public function translations()
    {
        return $this->hasMany(ShippingCarrierTranslation::class, 'carrier_id');
    }

    /**
     * 获取指定国家可用的运输商列表
     *
     * @param string $countryCode 国家代码
     * @param float $orderAmount 订单金额（用于计算免运费）
     * @param string $locale 语言代码
     * @return array
     */
    public static function getAvailableCarriers(string $countryCode, float $orderAmount = 0, string $locale = 'en-us'): array
    {
        // 获取启用的运输商
        $carriers = self::where('status', self::STATUS_ENABLED)
            ->order('sort', 'desc')
            ->order('id', 'asc')
            ->select();

        $result = [];
        foreach ($carriers as $carrier) {
            // 查找该国家的配置
            $countryConfig = ShippingCarrierCountry::where('carrier_id', $carrier->id)
                ->where('country_code', $countryCode)
                ->where('is_enabled', 1)
                ->find();

            // 如果该国家没有配置，跳过此运输商
            if (!$countryConfig) {
                continue;
            }

            // 计算实际运费
            $shippingFee = (float) $countryConfig->shipping_fee;
            $isFreeShipping = false;

            // 检查是否满足免运费条件
            if ($countryConfig->free_shipping_threshold !== null && $orderAmount >= $countryConfig->free_shipping_threshold) {
                $shippingFee = 0;
                $isFreeShipping = true;
            }

            // 配送时间（国家配置优先，否则使用默认值）
            $estimatedDaysMin = $countryConfig->estimated_days_min ?? $carrier->estimated_days_min;
            $estimatedDaysMax = $countryConfig->estimated_days_max ?? $carrier->estimated_days_max;

            // 获取翻译
            $name = $carrier->name;
            $description = $carrier->description;

            $normalizedLocale = strtolower($locale);
            $translation = ShippingCarrierTranslation::where('carrier_id', $carrier->id)
                ->where('locale', $normalizedLocale)
                ->find();

            if ($translation) {
                if (!empty($translation->name)) {
                    $name = $translation->name;
                }
                if (!empty($translation->description)) {
                    $description = $translation->description;
                }
            }

            $result[] = [
                'id' => $carrier->id,
                'code' => $carrier->code,
                'name' => $name,
                'description' => $description,
                'logo' => UrlHelper::getFullUrl($carrier->logo ?? ''),
                'shipping_fee' => round($shippingFee, 2),
                'original_fee' => round((float) $countryConfig->shipping_fee, 2),
                'currency' => $countryConfig->currency,
                'is_free_shipping' => $isFreeShipping,
                'free_shipping_threshold' => $countryConfig->free_shipping_threshold,
                'estimated_days_min' => $estimatedDaysMin,
                'estimated_days_max' => $estimatedDaysMax,
                'estimated_days' => $estimatedDaysMin && $estimatedDaysMax
                    ? "{$estimatedDaysMin}-{$estimatedDaysMax}"
                    : null,
            ];
        }

        return $result;
    }

    /**
     * 获取运输商详情（用于订单）
     */
    public static function getCarrierForOrder(int $carrierId, string $countryCode): ?array
    {
        $carrier = self::find($carrierId);
        if (!$carrier || $carrier->status !== self::STATUS_ENABLED) {
            return null;
        }

        $countryConfig = ShippingCarrierCountry::where('carrier_id', $carrierId)
            ->where('country_code', $countryCode)
            ->where('is_enabled', 1)
            ->find();

        if (!$countryConfig) {
            return null;
        }

        return [
            'id' => $carrier->id,
            'code' => $carrier->code,
            'name' => $carrier->name,
            'logo' => UrlHelper::getFullUrl($carrier->logo ?? ''),
            'tracking_url' => $carrier->tracking_url,
            'shipping_fee' => (float) $countryConfig->shipping_fee,
            'currency' => $countryConfig->currency,
            'free_shipping_threshold' => $countryConfig->free_shipping_threshold,
            'estimated_days_min' => $countryConfig->estimated_days_min ?? $carrier->estimated_days_min,
            'estimated_days_max' => $countryConfig->estimated_days_max ?? $carrier->estimated_days_max,
        ];
    }

    /**
     * 获取所有运输商列表（管理端用）
     */
    public static function getAllCarriers(string $locale = 'en-us'): array
    {
        $list = self::order('sort', 'desc')
            ->order('id', 'asc')
            ->select()
            ->toArray();

        return self::appendTranslations($list, $locale);
    }
}
