<?php
declare(strict_types=1);

namespace app\admin\controller;

use think\Response;
use app\common\model\PaymentMethod as PaymentMethodModel;

/**
 * 支付方式管理控制器
 */
class PaymentMethod extends Base
{
    /**
     * 支持的语言列表
     */
    protected $supportedLocales = ['zh-tw', 'en-us', 'ja-jp'];

    /**
     * 支付方式列表
     */
    public function index(): Response
    {
        [$page, $pageSize] = $this->getPageParams();
        $locale = input('locale', 'zh-tw');

        $query = PaymentMethodModel::order('sort', 'desc')->order('id', 'asc');

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
        $list = PaymentMethodModel::appendTranslations($list, $locale);

        return $this->paginate($list, $total, $page, $pageSize);
    }

    /**
     * 支付方式详情
     */
    public function read(int $id): Response
    {
        $paymentMethod = PaymentMethodModel::find($id);
        if (!$paymentMethod) {
            return $this->error('支付方式不存在', 404);
        }

        $data = $paymentMethod->toArray();
        // 获取所有翻译
        $data['translations'] = [];
        foreach ($this->supportedLocales as $locale) {
            $translation = $paymentMethod->translation($locale);
            $data['translations'][$locale] = $translation ? $translation->toArray() : [
                'name' => '',
                'description' => '',
                'tag' => '',
                'link_text' => ''
            ];
        }

        return $this->success($data);
    }

    /**
     * 创建支付方式
     */
    public function create(): Response
    {
        $data = input('post.');
        $translations = $data['translations'] ?? [];

        // 验证必填字段
        if (empty($data['code'])) {
            return $this->error('请填写支付方式编码');
        }

        // 检查编码是否已存在
        $exists = PaymentMethodModel::where('code', $data['code'])->find();
        if ($exists) {
            return $this->error('支付方式编码已存在');
        }

        // 验证至少有一种语言的名称
        $hasName = false;
        foreach ($translations as $locale => $trans) {
            if (!empty($trans['name'])) {
                $hasName = true;
                break;
            }
        }
        if (!$hasName && empty($data['name'])) {
            return $this->error('请填写支付方式名称');
        }

        $paymentMethod = new PaymentMethodModel();
        $paymentMethod->code = $data['code'];
        $paymentMethod->name = $data['name'] ?? ($translations['en-us']['name'] ?? ($translations['zh-tw']['name'] ?? ''));
        $paymentMethod->description = $data['description'] ?? null;
        $paymentMethod->icon = $data['icon'] ?? null;
        $paymentMethod->brand_color = $data['brand_color'] ?? null;
        $paymentMethod->button_icon = $data['button_icon'] ?? null;
        $paymentMethod->tag = $data['tag'] ?? null;
        $paymentMethod->link_text = $data['link_text'] ?? null;
        $paymentMethod->link_url = $data['link_url'] ?? null;
        $paymentMethod->config = $data['config'] ?? null;
        $paymentMethod->sort = $data['sort'] ?? 0;
        $paymentMethod->status = $data['status'] ?? 1;
        $paymentMethod->save();

        // 保存翻译
        foreach ($this->supportedLocales as $locale) {
            if (isset($translations[$locale])) {
                $paymentMethod->saveTranslation($locale, $translations[$locale]);
            }
        }

        return $this->success(['id' => $paymentMethod->id], '创建成功');
    }

    /**
     * 更新支付方式
     */
    public function update(int $id): Response
    {
        $paymentMethod = PaymentMethodModel::find($id);
        if (!$paymentMethod) {
            return $this->error('支付方式不存在', 404);
        }

        $data = input('post.');
        $translations = $data['translations'] ?? [];

        // 检查编码是否已被其他记录使用
        if (!empty($data['code']) && $data['code'] !== $paymentMethod->code) {
            $exists = PaymentMethodModel::where('code', $data['code'])
                ->where('id', '<>', $id)
                ->find();
            if ($exists) {
                return $this->error('支付方式编码已存在');
            }
        }

        $allowFields = ['code', 'name', 'description', 'icon', 'brand_color', 'button_icon',
                        'tag', 'link_text', 'link_url', 'config', 'sort', 'status'];

        foreach ($allowFields as $field) {
            if (isset($data[$field])) {
                $paymentMethod->$field = $data[$field];
            }
        }
        $paymentMethod->save();

        // 更新翻译
        foreach ($this->supportedLocales as $locale) {
            if (isset($translations[$locale])) {
                $paymentMethod->saveTranslation($locale, $translations[$locale]);
            }
        }

        return $this->success([], '更新成功');
    }

    /**
     * 删除支付方式
     */
    public function delete(int $id): Response
    {
        $paymentMethod = PaymentMethodModel::find($id);
        if (!$paymentMethod) {
            return $this->error('支付方式不存在', 404);
        }

        $paymentMethod->delete();
        return $this->success([], '删除成功');
    }

    /**
     * 切换状态
     */
    public function toggleStatus(int $id): Response
    {
        $paymentMethod = PaymentMethodModel::find($id);
        if (!$paymentMethod) {
            return $this->error('支付方式不存在', 404);
        }

        $paymentMethod->status = $paymentMethod->status == 1 ? 0 : 1;
        $paymentMethod->save();

        return $this->success([
            'status' => $paymentMethod->status
        ], $paymentMethod->status == 1 ? '已启用' : '已禁用');
    }

    /**
     * 获取所有启用的支付方式（供APP端调用）
     */
    public function all(): Response
    {
        $locale = input('locale', 'en-us');
        $list = PaymentMethodModel::getEnabledList($locale);
        return $this->success($list);
    }
}
