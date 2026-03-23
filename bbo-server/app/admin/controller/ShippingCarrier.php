<?php
declare(strict_types=1);

namespace app\admin\controller;

use think\Response;
use app\common\model\ShippingCarrier as ShippingCarrierModel;
use app\common\model\ShippingCarrierTranslation;
use app\common\model\ShippingCarrierCountry;

/**
 * 物流运输商管理控制器
 */
class ShippingCarrier extends Base
{
    /**
     * 支持的语言列表
     */
    protected $supportedLocales = ['zh-tw', 'en-us', 'ja-jp'];

    /**
     * 运输商列表
     */
    public function index(): Response
    {
        [$page, $pageSize] = $this->getPageParams();
        $locale = input('locale', 'zh-tw');

        $query = ShippingCarrierModel::order('sort', 'desc')->order('id', 'asc');

        $status = input('status', '');
        if ($status !== '') {
            $query->where('status', (int)$status);
        }

        $keyword = input('keyword', '');
        if ($keyword) {
            $query->where('name|code', 'like', "%{$keyword}%");
        }

        $total = $query->count();
        $list = $query->page($page, $pageSize)->select()->toArray();

        // 附加翻译
        $list = ShippingCarrierModel::appendTranslations($list, $locale);

        // 附加国家数量
        foreach ($list as &$item) {
            $item['country_count'] = ShippingCarrierCountry::where('carrier_id', $item['id'])->count();
        }

        return $this->paginate($list, $total, $page, $pageSize);
    }

    /**
     * 运输商详情
     */
    public function read(int $id): Response
    {
        $carrier = ShippingCarrierModel::find($id);
        if (!$carrier) {
            return $this->error('运输商不存在', 404);
        }

        $data = $carrier->toArray();

        // 获取所有翻译
        $data['translations'] = [];
        foreach ($this->supportedLocales as $locale) {
            $translation = ShippingCarrierTranslation::where('carrier_id', $id)
                ->where('locale', $locale)
                ->find();
            $data['translations'][$locale] = $translation ? [
                'name' => $translation->name ?? '',
                'description' => $translation->description ?? ''
            ] : [
                'name' => '',
                'description' => ''
            ];
        }

        // 获取国家配置
        $data['countries'] = ShippingCarrierCountry::getCarrierCountries($id);

        return $this->success($data);
    }

    /**
     * 创建运输商
     */
    public function create(): Response
    {
        $data = input('post.');
        $translations = $data['translations'] ?? [];
        $countries = $data['countries'] ?? [];

        // 验证必填字段
        if (empty($data['code'])) {
            return $this->error('请填写运输商编码');
        }

        // 检查编码是否已存在
        $exists = ShippingCarrierModel::where('code', $data['code'])->find();
        if ($exists) {
            return $this->error('运输商编码已存在');
        }

        // 验证至少有一种语言的名称
        $hasName = false;
        foreach ($translations as $trans) {
            if (!empty($trans['name'])) {
                $hasName = true;
                break;
            }
        }
        if (!$hasName && empty($data['name'])) {
            return $this->error('请填写运输商名称');
        }

        $carrier = new ShippingCarrierModel();
        $carrier->code = $data['code'];
        $carrier->name = $data['name'] ?? ($translations['en-us']['name'] ?? ($translations['zh-tw']['name'] ?? ''));
        $carrier->description = $data['description'] ?? null;
        $carrier->logo = $data['logo'] ?? null;
        $carrier->tracking_url = $data['tracking_url'] ?? null;
        $carrier->estimated_days_min = $data['estimated_days_min'] ?? null;
        $carrier->estimated_days_max = $data['estimated_days_max'] ?? null;
        $carrier->sort = $data['sort'] ?? 0;
        $carrier->status = $data['status'] ?? 1;
        $carrier->save();

        // 保存翻译
        foreach ($this->supportedLocales as $locale) {
            if (isset($translations[$locale]) && !empty($translations[$locale]['name'])) {
                ShippingCarrierTranslation::create([
                    'carrier_id' => $carrier->id,
                    'locale' => $locale,
                    'name' => $translations[$locale]['name'] ?? null,
                    'description' => $translations[$locale]['description'] ?? null,
                    'is_original' => $locale === 'en-us' ? 1 : 0,
                ]);
            }
        }

        // 保存国家配置
        if (!empty($countries)) {
            ShippingCarrierCountry::saveCountries($carrier->id, $countries);
        }

        return $this->success(['id' => $carrier->id], '创建成功');
    }

    /**
     * 更新运输商
     */
    public function update(int $id): Response
    {
        $carrier = ShippingCarrierModel::find($id);
        if (!$carrier) {
            return $this->error('运输商不存在', 404);
        }

        $data = input('post.');
        $translations = $data['translations'] ?? [];
        $countries = $data['countries'] ?? null;

        // 检查编码是否已被其他记录使用
        if (!empty($data['code']) && $data['code'] !== $carrier->code) {
            $exists = ShippingCarrierModel::where('code', $data['code'])
                ->where('id', '<>', $id)
                ->find();
            if ($exists) {
                return $this->error('运输商编码已存在');
            }
        }

        $allowFields = ['code', 'name', 'description', 'logo', 'tracking_url',
                        'estimated_days_min', 'estimated_days_max', 'sort', 'status'];

        foreach ($allowFields as $field) {
            if (isset($data[$field])) {
                $carrier->$field = $data[$field];
            }
        }
        $carrier->save();

        // 更新翻译
        foreach ($this->supportedLocales as $locale) {
            if (isset($translations[$locale])) {
                $existing = ShippingCarrierTranslation::where('carrier_id', $id)
                    ->where('locale', $locale)
                    ->find();

                if ($existing) {
                    $existing->name = $translations[$locale]['name'] ?? null;
                    $existing->description = $translations[$locale]['description'] ?? null;
                    $existing->save();
                } else if (!empty($translations[$locale]['name'])) {
                    ShippingCarrierTranslation::create([
                        'carrier_id' => $id,
                        'locale' => $locale,
                        'name' => $translations[$locale]['name'] ?? null,
                        'description' => $translations[$locale]['description'] ?? null,
                        'is_original' => $locale === 'en-us' ? 1 : 0,
                    ]);
                }
            }
        }

        // 更新国家配置（如果提供）
        if ($countries !== null) {
            ShippingCarrierCountry::saveCountries($id, $countries);
        }

        return $this->success([], '更新成功');
    }

    /**
     * 删除运输商
     */
    public function delete(int $id): Response
    {
        $carrier = ShippingCarrierModel::find($id);
        if (!$carrier) {
            return $this->error('运输商不存在', 404);
        }

        // 翻译和国家配置会通过外键约束自动删除
        $carrier->delete();
        return $this->success([], '删除成功');
    }

    /**
     * 切换状态
     */
    public function toggleStatus(int $id): Response
    {
        $carrier = ShippingCarrierModel::find($id);
        if (!$carrier) {
            return $this->error('运输商不存在', 404);
        }

        $carrier->status = $carrier->status == 1 ? 0 : 1;
        $carrier->save();

        return $this->success([
            'status' => $carrier->status
        ], $carrier->status == 1 ? '已启用' : '已禁用');
    }

    /**
     * 获取运输商的国家配置
     */
    public function countries(int $id): Response
    {
        $carrier = ShippingCarrierModel::find($id);
        if (!$carrier) {
            return $this->error('运输商不存在', 404);
        }

        $countries = ShippingCarrierCountry::getCarrierCountries($id);
        return $this->success($countries);
    }

    /**
     * 更新运输商的国家配置
     */
    public function saveCountries(int $id): Response
    {
        $carrier = ShippingCarrierModel::find($id);
        if (!$carrier) {
            return $this->error('运输商不存在', 404);
        }

        $countries = input('post.countries', []);
        ShippingCarrierCountry::saveCountries($id, $countries);

        return $this->success([], '国家配置已更新');
    }
}
