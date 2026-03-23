<?php
declare(strict_types=1);

namespace app\api\controller;

use think\Response;
use think\facade\Db;
use app\common\model\Brand as BrandModel;
use app\common\model\CategoryAttribute;
use app\common\model\AttributeOption;
use app\common\model\Goods;
use app\common\helper\UrlHelper;

/**
 * 品牌控制器 (API端)
 */
class Brand extends Base
{
    /**
     * 获取品牌列表 (启用的)
     * @return Response
     */
    public function index(): Response
    {
        $list = BrandModel::getEnabledList($this->locale);

        return $this->success($list);
    }

    /**
     * 获取品牌下的所有产品系列（跨分类）
     * @param string $value 品牌值
     * @return Response
     */
    public function models(string $value): Response
    {
        // 获取全局品牌属性
        $brandAttr = CategoryAttribute::where('attr_key', 'brand')
            ->where('category_id', 0)
            ->where('status', 1)
            ->find();

        if (!$brandAttr) {
            return $this->success([
                'brand_value' => $value,
                'brand_name' => ucfirst($value),
                'brand_image' => null,
                'models' => [],
            ]);
        }

        // 获取品牌选项
        $brandOption = AttributeOption::where('attribute_id', $brandAttr->id)
            ->where('option_value', $value)
            ->where('status', 1)
            ->find();

        if (!$brandOption) {
            return $this->error('Brand not found', 404);
        }

        // 附加翻译
        $brandData = AttributeOption::appendTranslations([$brandOption->toArray()], $this->locale)[0];
        $brandName = $brandData['label'] ?? ucfirst($value);
        $brandImage = $brandData['image'] ?? null;

        // 获取所有分类的 model 属性
        $modelAttrs = CategoryAttribute::where('attr_key', 'model')
            ->where('status', 1)
            ->select();

        if ($modelAttrs->isEmpty()) {
            return $this->success([
                'brand_value' => $value,
                'brand_name' => $brandName,
                'brand_image' => $brandImage,
                'models' => [],
            ]);
        }

        $modelAttrIds = $modelAttrs->column('id');
        $categoryMap = [];
        foreach ($modelAttrs as $attr) {
            $categoryMap[$attr->id] = $attr->category_id;
        }

        // 获取该品牌下所有型号选项（parent_value = 品牌值）
        $modelOptions = AttributeOption::where('attribute_id', 'in', $modelAttrIds)
            ->where('parent_value', $value)
            ->where('status', 1)
            ->order('sort', 'desc')
            ->select()
            ->toArray();

        if (empty($modelOptions)) {
            return $this->success([
                'brand_value' => $value,
                'brand_name' => $brandName,
                'brand_image' => $brandImage,
                'models' => [],
            ]);
        }

        // 附加翻译
        $modelOptions = AttributeOption::appendTranslations($modelOptions, $this->locale);

        // 获取每个型号的最低价格
        $modelValues = array_column($modelOptions, 'option_value');
        $minPrices = $this->getAllModelMinPrices($modelValues);

        // 构建型号列表（只保留有实际商品的型号）
        $models = [];
        foreach ($modelOptions as $model) {
            $modelMinPrice = $minPrices[$model['option_value']] ?? null;
            if ($modelMinPrice === null) {
                continue;
            }
            $models[] = [
                'value' => $model['option_value'],
                'name' => $model['label'] ?? $model['option_value'],
                'image' => UrlHelper::getFullUrl($model['image'] ?? null),
                'min_price' => $modelMinPrice,
                'category_id' => $categoryMap[$model['attribute_id']] ?? null,
            ];
        }

        return $this->success([
            'brand_value' => $value,
            'brand_name' => $brandName,
            'brand_image' => UrlHelper::getFullUrl($brandImage),
            'models' => $models,
        ]);
    }

    /**
     * 获取所有型号的最低价格（跨分类）
     * @param array $modelValues
     * @return array
     */
    private function getAllModelMinPrices(array $modelValues): array
    {
        if (empty($modelValues)) {
            return [];
        }

        $minPrices = [];

        // 从所有上架商品的 specs JSON 中提取 model 值并计算最低价格
        $goods = Db::table('goods')
            ->alias('g')
            ->join('goods_translations gt', 'g.id = gt.goods_id')
            ->where('g.status', Goods::STATUS_ON_SALE)
            ->whereNull('g.deleted_at')
            ->field('g.price, gt.specs')
            ->select();

        foreach ($goods as $item) {
            $specs = json_decode($item['specs'] ?? '{}', true);
            $modelValue = $specs['model'] ?? null;

            if ($modelValue && in_array($modelValue, $modelValues)) {
                if (!isset($minPrices[$modelValue]) || $item['price'] < $minPrices[$modelValue]) {
                    $minPrices[$modelValue] = (float)$item['price'];
                }
            }
        }

        return $minPrices;
    }
}
