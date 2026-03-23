<?php
declare(strict_types=1);

namespace app\api\controller;

use think\Response;
use app\common\model\ShippingCarrier;
use app\common\helper\UrlHelper;

/**
 * 物流运输商控制器
 */
class Shipping extends Base
{
    /**
     * 获取指定国家可用的运输商列表
     * 如果不传 country_code，返回所有启用的运输商（用于卖家发货选择）
     *
     * @return Response
     */
    public function carriers(): Response
    {
        $countryCode = input('country_code', '');
        $orderAmount = (float) input('order_amount', 0);

        // 如果没有传国家代码，返回所有启用的运输商（用于卖家发货）
        if (empty($countryCode)) {
            $carriers = ShippingCarrier::where('status', ShippingCarrier::STATUS_ENABLED)
                ->order('sort', 'desc')
                ->order('id', 'asc')
                ->select();

            $result = [];
            foreach ($carriers as $carrier) {
                $result[] = [
                    'id' => $carrier->id,
                    'code' => $carrier->code,
                    'name' => $carrier->getTranslated('name', $this->locale),
                    'logo' => UrlHelper::getFullUrl($carrier->logo ?? ''),
                    'estimated_days_min' => $carrier->estimated_days_min,
                    'estimated_days_max' => $carrier->estimated_days_max,
                ];
            }
            return $this->success($result);
        }

        $carriers = ShippingCarrier::getAvailableCarriers(
            $countryCode,
            $orderAmount,
            $this->locale
        );

        return $this->success($carriers);
    }

    /**
     * 获取运输商详情
     *
     * @return Response
     */
    public function detail(): Response
    {
        $carrierId = (int) input('id', 0);
        $countryCode = input('country_code', '');

        if ($carrierId <= 0) {
            return $this->error('Invalid carrier ID');
        }

        if (empty($countryCode)) {
            return $this->error('Country code is required');
        }

        $carrier = ShippingCarrier::getCarrierForOrder($carrierId, $countryCode);

        if (!$carrier) {
            return $this->error('Carrier not available for this country');
        }

        return $this->success($carrier);
    }
}
