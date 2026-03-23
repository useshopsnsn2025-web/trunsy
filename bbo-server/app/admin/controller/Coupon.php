<?php
declare(strict_types=1);

namespace app\admin\controller;

use think\Response;
use app\common\model\Coupon as CouponModel;

/**
 * 优惠券管理控制器
 */
class Coupon extends Base
{
    /**
     * 支持的语言列表
     */
    protected $supportedLocales = ['zh-tw', 'en-us', 'ja-jp'];

    /**
     * 优惠券列表
     */
    public function index(): Response
    {
        [$page, $pageSize] = $this->getPageParams();
        $locale = input('locale', 'zh-tw');

        $query = CouponModel::order('id', 'desc');

        $keyword = input('keyword', '');
        if ($keyword) {
            $query->where(function ($q) use ($keyword) {
                $q->where('name', 'like', "%{$keyword}%")
                    ->whereOr('code', 'like', "%{$keyword}%");
            });
        }

        $type = input('type', '');
        if ($type !== '') {
            $query->where('type', (int)$type);
        }

        $status = input('status', '');
        if ($status !== '') {
            $query->where('status', (int)$status);
        }

        $total = $query->count();
        $list = $query->page($page, $pageSize)->select()->toArray();

        // 附加翻译
        $list = CouponModel::appendTranslations($list, $locale);

        return $this->paginate($list, $total, $page, $pageSize);
    }

    /**
     * 优惠券详情
     */
    public function read(int $id): Response
    {
        $coupon = CouponModel::find($id);
        if (!$coupon) {
            return $this->error('优惠券不存在', 404);
        }

        $data = $coupon->toArray();
        // 获取所有翻译
        $data['translations'] = [];
        foreach ($this->supportedLocales as $locale) {
            $translation = $coupon->translation($locale);
            $data['translations'][$locale] = $translation ? $translation->toArray() : [
                'name' => '',
                'description' => ''
            ];
        }

        return $this->success($data);
    }

    /**
     * 创建优惠券
     */
    public function create(): Response
    {
        $data = input('post.');
        $translations = $data['translations'] ?? [];

        // 验证至少有一种语言的名称
        $hasName = false;
        foreach ($translations as $locale => $trans) {
            if (!empty($trans['name'])) {
                $hasName = true;
                break;
            }
        }
        if (!$hasName && empty($data['name'])) {
            return $this->error('请填写优惠券名称');
        }
        if (empty($data['code'])) {
            return $this->error('请填写优惠券代码');
        }

        $exists = CouponModel::where('code', $data['code'])->find();
        if ($exists) {
            return $this->error('优惠券代码已存在');
        }

        $coupon = new CouponModel();
        $coupon->name = $data['name'] ?? ($translations['zh-tw']['name'] ?? '');
        $coupon->code = strtoupper($data['code']);
        $coupon->type = $data['type'] ?? 1;
        $coupon->value = $data['value'] ?? 0;
        $coupon->min_amount = $data['min_amount'] ?? 0;
        $coupon->max_discount = $data['max_discount'] ?? null;
        $coupon->total_count = $data['total_count'] ?? 0;
        $coupon->per_limit = $data['per_limit'] ?? 1;
        $coupon->start_time = $data['start_time'];
        $coupon->end_time = $data['end_time'];
        $coupon->scope = $data['scope'] ?? 1;
        $coupon->scope_ids = $data['scope_ids'] ?? null;
        $coupon->description = $data['description'] ?? ($translations['zh-tw']['description'] ?? null);
        $coupon->image = $data['image'] ?? null;
        $coupon->status = $data['status'] ?? 1;
        $coupon->save();

        // 保存翻译
        foreach ($this->supportedLocales as $locale) {
            if (isset($translations[$locale])) {
                $coupon->saveTranslation($locale, $translations[$locale]);
            }
        }

        return $this->success(['id' => $coupon->id], '创建成功');
    }

    /**
     * 更新优惠券
     */
    public function update(int $id): Response
    {
        $coupon = CouponModel::find($id);
        if (!$coupon) {
            return $this->error('优惠券不存在', 404);
        }

        $data = input('post.');
        $translations = $data['translations'] ?? [];

        $allowFields = ['name', 'value', 'min_amount', 'max_discount', 'total_count',
                        'per_limit', 'start_time', 'end_time', 'scope', 'scope_ids',
                        'description', 'image', 'status'];

        foreach ($allowFields as $field) {
            if (isset($data[$field])) {
                $coupon->$field = $data[$field];
            }
        }
        $coupon->save();

        // 更新翻译
        foreach ($this->supportedLocales as $locale) {
            if (isset($translations[$locale])) {
                $coupon->saveTranslation($locale, $translations[$locale]);
            }
        }

        return $this->success([], '更新成功');
    }

    /**
     * 删除优惠券
     */
    public function delete(int $id): Response
    {
        $coupon = CouponModel::find($id);
        if (!$coupon) {
            return $this->error('优惠券不存在', 404);
        }

        if ($coupon->used_count > 0) {
            return $this->error('该优惠券已有使用记录，无法删除');
        }

        $coupon->delete();
        return $this->success([], '删除成功');
    }

    /**
     * 统计数据
     */
    public function statistics(): Response
    {
        $total = CouponModel::count();
        $active = CouponModel::where('status', 1)
            ->where('end_time', '>=', date('Y-m-d H:i:s'))
            ->count();
        $totalReceived = CouponModel::sum('received_count');
        $totalUsed = CouponModel::sum('used_count');

        return $this->success([
            'total' => $total,
            'active' => $active,
            'total_received' => (int)$totalReceived,
            'total_used' => (int)$totalUsed,
        ]);
    }
}
