<?php
declare(strict_types=1);

namespace app\api\controller;

use think\Response;
use think\facade\Db;
use app\common\model\Category as CategoryModel;
use app\common\model\CategoryAttribute;
use app\common\model\AttributeOption;
use app\common\model\Goods;

/**
 * 分类控制器
 */
class Category extends Base
{
    /**
     * 获取分类列表
     * @return Response
     */
    public function index(): Response
    {
        $tree = CategoryModel::getTree($this->locale);

        return $this->success($tree);
    }

    /**
     * 获取热门分类列表
     * @return Response
     */
    public function hot(): Response
    {
        $list = CategoryModel::getHotCategories($this->locale);

        return $this->success($list);
    }

    /**
     * 获取分类的属性模板
     * @param int $id 分类ID
     * @return Response
     */
    public function attributes(int $id): Response
    {
        // 验证分类是否存在
        $category = CategoryModel::where('id', $id)
            ->where('status', 1)
            ->find();

        if (!$category) {
            return $this->error('Category not found', 404);
        }

        // 获取属性列表
        $attributes = CategoryAttribute::getAttributesByCategoryId($id, $this->locale);

        return $this->success([
            'category_id' => $id,
            'attributes' => $attributes,
        ]);
    }

    /**
     * 获取分类下的品牌和系列列表
     * @param int $id 分类ID
     * @return Response
     */
    public function brands(int $id): Response
    {
        // 验证分类是否存在
        $category = CategoryModel::where('id', $id)
            ->where('status', 1)
            ->find();

        if (!$category) {
            return $this->error('Category not found', 404);
        }

        // 获取分类名称翻译
        $categoryData = $category->toArray();
        $categoryData = CategoryModel::appendTranslations([$categoryData], $this->locale)[0];

        // 获取全局品牌属性
        $brandAttr = CategoryAttribute::where('attr_key', 'brand')
            ->where('category_id', 0)
            ->where('status', 1)
            ->find();

        if (!$brandAttr) {
            return $this->success([
                'category_id' => $id,
                'category_name' => $categoryData['name'] ?? $category->name,
                'brands' => [],
            ]);
        }

        // 获取该分类的型号/系列属性（优先 model/Model，其次 Series）
        $modelAttr = CategoryAttribute::whereRaw("LOWER(attr_key) = 'model'")
            ->where('category_id', $id)
            ->where('status', 1)
            ->find();

        $modelOptions = [];
        if ($modelAttr) {
            $modelOptions = AttributeOption::where('attribute_id', $modelAttr->id)
                ->where('status', 1)
                ->order('sort', 'desc')
                ->select()
                ->toArray();
        }

        // model 没有选项时，尝试用 Series 属性
        if (empty($modelOptions)) {
            $seriesAttr = CategoryAttribute::where('attr_key', 'Series')
                ->where('category_id', $id)
                ->where('status', 1)
                ->find();

            if ($seriesAttr) {
                $modelAttr = $seriesAttr;
                $modelOptions = AttributeOption::where('attribute_id', $seriesAttr->id)
                    ->where('status', 1)
                    ->order('sort', 'desc')
                    ->select()
                    ->toArray();
            }
        }

        // 仍然没有选项时，从商品 specs 中提取品牌和型号
        if (empty($modelOptions)) {
            $brands = $this->getBrandsFromSpecs($id, $brandAttr, $this->locale);

            return $this->success([
                'category_id' => $id,
                'category_name' => $categoryData['name'] ?? $category->name,
                'brands' => $brands,
            ]);
        }

        // 附加翻译
        $modelOptions = AttributeOption::appendTranslations($modelOptions, $this->locale);

        // 获取所有涉及的品牌值
        $brandValues = array_unique(array_filter(array_column($modelOptions, 'parent_value')));

        // 获取品牌选项及翻译
        $brandOptions = AttributeOption::where('attribute_id', $brandAttr->id)
            ->where('option_value', 'in', $brandValues)
            ->where('status', 1)
            ->order('sort', 'desc')
            ->select()
            ->toArray();

        $brandOptions = AttributeOption::appendTranslations($brandOptions, $this->locale);

        // 构建品牌名称映射
        $brandNameMap = [];
        $brandImageMap = [];
        foreach ($brandOptions as $brand) {
            $brandNameMap[$brand['option_value']] = $brand['label'] ?? $brand['option_value'];
            $brandImageMap[$brand['option_value']] = $brand['image'] ?? null;
        }

        // 获取每个型号的最低价格
        $modelValues = array_column($modelOptions, 'option_value');
        $attrKey = $modelAttr->getData('attr_key') ?? $modelAttr->attr_key ?? 'model';
        $minPrices = $this->getModelMinPrices($id, $modelValues, $attrKey);

        // 按品牌分组型号（只保留有实际商品的型号）
        $brandModels = [];
        foreach ($modelOptions as $model) {
            $brandValue = $model['parent_value'] ?? '';
            if (empty($brandValue)) {
                continue;
            }

            // 跳过没有商品的型号（min_price 为 null 说明该型号没有上架商品）
            $modelMinPrice = $minPrices[$model['option_value']] ?? null;
            if ($modelMinPrice === null) {
                continue;
            }

            if (!isset($brandModels[$brandValue])) {
                $brandModels[$brandValue] = [
                    'brand_value' => $brandValue,
                    'brand_name' => $brandNameMap[$brandValue] ?? ucfirst($brandValue),
                    'brand_image' => $brandImageMap[$brandValue] ?? null,
                    'models' => [],
                ];
            }

            $brandModels[$brandValue]['models'][] = [
                'value' => $model['option_value'],
                'name' => $model['label'] ?? $model['option_value'],
                'image' => $model['image'] ?? null,
                'min_price' => $modelMinPrice,
            ];
        }

        // 统计每个品牌的上架商品数量
        $brandGoodsCounts = $this->getBrandGoodsCounts($id, array_keys($brandModels));

        // 转换为数组并按商品数量降序排列（热门品牌优先），数量相同按品牌名排序
        $brands = array_values($brandModels);
        usort($brands, function ($a, $b) use ($brandGoodsCounts) {
            $countA = $brandGoodsCounts[$a['brand_value']] ?? 0;
            $countB = $brandGoodsCounts[$b['brand_value']] ?? 0;
            if ($countA !== $countB) {
                return $countB - $countA;
            }
            return strcasecmp($a['brand_name'], $b['brand_name']);
        });

        return $this->success([
            'category_id' => $id,
            'category_name' => $categoryData['name'] ?? $category->name,
            'brands' => $brands,
        ]);
    }

    /**
     * 获取分类的商品条件组列表（含选项）
     * @param int $id 分类ID
     * @return Response
     */
    public function conditions(int $id): Response
    {
        // 验证分类是否存在
        $category = CategoryModel::where('id', $id)
            ->where('status', 1)
            ->find();

        if (!$category) {
            return $this->error('Category not found', 404);
        }

        // 获取条件组及选项
        $groups = \app\common\model\CategoryConditionGroup::getGroupsWithOptions($id, $this->locale);

        // 格式化输出
        $formattedGroups = array_map(function ($group) {
            return [
                'id' => $group['id'],
                'name' => $group['name'],
                'icon' => $group['icon'] ?? null,
                'is_required' => (bool)$group['is_required'],
                'options' => array_map(function ($option) {
                    return [
                        'id' => $option['id'],
                        'name' => $option['name'],
                        'impact_level' => $option['impact_level'] ?? null,
                    ];
                }, $group['options'] ?? []),
            ];
        }, $groups);

        return $this->success([
            'category_id' => $id,
            'condition_groups' => $formattedGroups,
        ]);
    }

    /**
     * 从商品 specs 中提取品牌列表（用于没有型号选项的分类）
     * @param int $categoryId
     * @param CategoryAttribute $brandAttr
     * @param string $locale
     * @return array
     */
    private function getBrandsFromSpecs(int $categoryId, $brandAttr, string $locale): array
    {
        // 从商品 specs 中统计品牌和型号/系列
        $goods = Db::table('goods')
            ->alias('g')
            ->join('goods_translations gt', 'g.id = gt.goods_id AND gt.locale = \'en-us\'')
            ->where('g.category_id', $categoryId)
            ->where('g.status', Goods::STATUS_ON_SALE)
            ->whereNull('g.deleted_at')
            ->field('g.id, g.price, g.images, gt.specs')
            ->group('g.id')
            ->select();

        // brandKey => [model/series => [count, min_price, image]]
        $brandData = [];
        foreach ($goods as $item) {
            $specs = json_decode($item['specs'] ?? '{}', true);
            $brand = $specs['brand'] ?? null;
            if (empty($brand)) {
                continue;
            }
            $brandKey = strtolower($brand);

            // 从 specs 中提取型号/系列（优先 model，其次 series/Series）
            $modelName = $specs['model'] ?? '';
            if (empty($modelName)) {
                $modelName = $specs['series'] ?? $specs['Series'] ?? '';
            }

            // 获取商品第一张图片
            $images = json_decode($item['images'] ?? '[]', true);
            $firstImage = !empty($images) ? $images[0] : null;

            if (!isset($brandData[$brandKey])) {
                $brandData[$brandKey] = [
                    'count' => 0,
                    'min_price' => null,
                    'models' => [],
                ];
            }
            $brandData[$brandKey]['count']++;
            $price = (float)$item['price'];
            if ($brandData[$brandKey]['min_price'] === null || $price < $brandData[$brandKey]['min_price']) {
                $brandData[$brandKey]['min_price'] = $price;
            }

            // 统计型号/系列
            if (!empty($modelName)) {
                if (!isset($brandData[$brandKey]['models'][$modelName])) {
                    $brandData[$brandKey]['models'][$modelName] = ['count' => 0, 'min_price' => null, 'image' => null];
                }
                $brandData[$brandKey]['models'][$modelName]['count']++;
                if ($brandData[$brandKey]['models'][$modelName]['min_price'] === null
                    || $price < $brandData[$brandKey]['models'][$modelName]['min_price']) {
                    $brandData[$brandKey]['models'][$modelName]['min_price'] = $price;
                }
                // 取第一个有图片的商品作为型号展示图
                if (empty($brandData[$brandKey]['models'][$modelName]['image']) && $firstImage) {
                    $brandData[$brandKey]['models'][$modelName]['image'] = $firstImage;
                }
            }
        }

        if (empty($brandData)) {
            return [];
        }

        // 按商品数量降序
        uasort($brandData, function ($a, $b) {
            return $b['count'] - $a['count'];
        });

        // 获取品牌选项的翻译和图片
        $brandOptions = AttributeOption::where('attribute_id', $brandAttr->id)
            ->where('status', 1)
            ->select()
            ->toArray();
        $brandOptions = AttributeOption::appendTranslations($brandOptions, $locale);

        $brandInfoMap = [];
        foreach ($brandOptions as $opt) {
            $key = strtolower($opt['option_value']);
            $brandInfoMap[$key] = [
                'label' => $opt['label'] ?? $opt['option_value'],
                'image' => $opt['image'] ?? null,
                'value' => $opt['option_value'],
            ];
        }

        // 构建返回数据
        $brands = [];
        foreach ($brandData as $brandKey => $data) {
            $info = $brandInfoMap[$brandKey] ?? null;

            // 构建型号子项（按数量降序）
            $models = [];
            if (!empty($data['models'])) {
                uasort($data['models'], function ($a, $b) {
                    return $b['count'] - $a['count'];
                });
                foreach ($data['models'] as $modelName => $modelData) {
                    $models[] = [
                        'value' => $modelName,
                        'name' => $modelName,
                        'image' => \app\common\helper\UrlHelper::getFullUrl($modelData['image'] ?? ''),
                        'min_price' => $modelData['min_price'],
                    ];
                }
            }

            $brands[] = [
                'brand_value' => $info['value'] ?? $brandKey,
                'brand_name' => $info['label'] ?? ucfirst($brandKey),
                'brand_image' => $info['image'] ?? null,
                'models' => $models,
                'goods_count' => $data['count'],
                'min_price' => $data['min_price'],
            ];
        }

        return $brands;
    }

    /**
     * 获取每个品牌的上架商品数量
     * @param int $categoryId
     * @param array $brandValues
     * @return array [brand_value => count]
     */
    private function getBrandGoodsCounts(int $categoryId, array $brandValues): array
    {
        if (empty($brandValues)) {
            return [];
        }

        $counts = [];
        foreach ($brandValues as $bv) {
            $counts[$bv] = 0;
        }

        // 用 GROUP BY g.id 避免多语言翻译导致重复计数
        $goods = Db::table('goods')
            ->alias('g')
            ->join('goods_translations gt', 'g.id = gt.goods_id AND gt.locale = \'en-us\'')
            ->where('g.category_id', $categoryId)
            ->where('g.status', Goods::STATUS_ON_SALE)
            ->whereNull('g.deleted_at')
            ->field('g.id, gt.specs')
            ->group('g.id')
            ->select();

        foreach ($goods as $item) {
            $specs = json_decode($item['specs'] ?? '{}', true);
            $brand = $specs['brand'] ?? null;
            if ($brand && isset($counts[$brand])) {
                $counts[$brand]++;
            }
        }

        return $counts;
    }

    /**
     * 获取型号的最低价格
     * @param int $categoryId
     * @param array $modelValues
     * @return array
     */
    private function getModelMinPrices(int $categoryId, array $modelValues, string $attrKey = 'model'): array
    {
        if (empty($modelValues)) {
            return [];
        }

        $minPrices = [];

        // 确定 specs 中的键名
        $specKeys = ['model'];
        if (strtolower($attrKey) === 'series') {
            $specKeys = ['series', 'Series', 'model'];
        }

        // 从 specs JSON 中提取值并计算最低价格
        $goods = Db::table('goods')
            ->alias('g')
            ->join('goods_translations gt', 'g.id = gt.goods_id AND gt.locale = \'en-us\'')
            ->where('g.category_id', $categoryId)
            ->where('g.status', Goods::STATUS_ON_SALE)
            ->whereNull('g.deleted_at')
            ->field('g.price, gt.specs')
            ->group('g.id')
            ->select();

        foreach ($goods as $item) {
            $specs = json_decode($item['specs'] ?? '{}', true);
            $matchedValue = null;
            foreach ($specKeys as $key) {
                if (!empty($specs[$key])) {
                    $matchedValue = $specs[$key];
                    break;
                }
            }

            if ($matchedValue && in_array($matchedValue, $modelValues)) {
                if (!isset($minPrices[$matchedValue]) || $item['price'] < $minPrices[$matchedValue]) {
                    $minPrices[$matchedValue] = (float)$item['price'];
                }
            }
        }

        return $minPrices;
    }
}
