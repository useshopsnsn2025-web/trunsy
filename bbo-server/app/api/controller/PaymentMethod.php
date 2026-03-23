<?php
declare(strict_types=1);

namespace app\api\controller;

use think\Response;
use app\common\model\PaymentMethod as PaymentMethodModel;
use app\common\helper\UrlHelper;

/**
 * 支付方式控制器 (API端)
 */
class PaymentMethod extends Base
{
    /**
     * 获取支付方式列表 (启用的)
     * @return Response
     */
    public function index(): Response
    {
        $list = PaymentMethodModel::getEnabledList($this->locale);

        // 格式化返回数据
        $result = [];
        foreach ($list as $item) {
            $result[] = [
                'id' => $item['code'],  // 使用code作为前端id
                'code' => $item['code'],
                'name' => $item['name'],
                'description' => $item['description'] ?? null,
                'icon' => UrlHelper::convertImageUrl($item['icon'] ?? null),
                'brand_color' => $item['brand_color'] ?? null,
                'button_icon' => UrlHelper::convertImageUrl($item['button_icon'] ?? null),
                'tag' => $item['tag'] ?? null,
                'link_text' => $item['link_text'] ?? null,
                'link_url' => $item['link_url'] ?? null,
            ];
        }

        return $this->success($result);
    }
}
