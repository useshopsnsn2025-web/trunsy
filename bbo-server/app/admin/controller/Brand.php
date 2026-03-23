<?php
declare(strict_types=1);

namespace app\admin\controller;

use think\Response;
use app\common\model\Brand as BrandModel;

/**
 * 品牌管理控制器
 */
class Brand extends Base
{
    /**
     * 支持的语言列表
     */
    protected $supportedLocales = ['zh-tw', 'en-us', 'ja-jp'];

    /**
     * 品牌列表
     */
    public function index(): Response
    {
        [$page, $pageSize] = $this->getPageParams();
        $locale = input('locale', 'zh-tw');

        $query = BrandModel::order('sort', 'desc')->order('id', 'desc');

        // 状态筛选
        $status = input('status', '');
        if ($status !== '') {
            $query->where('status', (int)$status);
        }

        // 关键词搜索
        $keyword = input('keyword', '');
        if ($keyword) {
            $query->where('name', 'like', '%' . $keyword . '%');
        }

        $total = $query->count();
        $list = $query->page($page, $pageSize)->select()->toArray();

        // 附加翻译
        $list = BrandModel::appendTranslations($list, $locale);

        return $this->paginate($list, $total, $page, $pageSize);
    }

    /**
     * 品牌详情
     */
    public function read(int $id): Response
    {
        $brand = BrandModel::find($id);
        if (!$brand) {
            return $this->error('品牌不存在', 404);
        }

        $data = $brand->toArray();
        // 获取所有翻译
        $data['translations'] = [];
        foreach ($this->supportedLocales as $locale) {
            $translation = $brand->translation($locale);
            $data['translations'][$locale] = $translation ? $translation->toArray() : [
                'name' => '',
                'description' => ''
            ];
        }

        return $this->success($data);
    }

    /**
     * 创建品牌
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
            return $this->error('请填写品牌名称');
        }

        $brand = new BrandModel();
        $brand->name = $data['name'] ?? ($translations['zh-tw']['name'] ?? '');
        $brand->logo = $data['logo'] ?? '';
        $brand->icon = $data['icon'] ?? null;
        $brand->sort = $data['sort'] ?? 0;
        $brand->status = $data['status'] ?? 1;
        $brand->save();

        // 保存翻译
        foreach ($this->supportedLocales as $locale) {
            if (isset($translations[$locale])) {
                $brand->saveTranslation($locale, $translations[$locale]);
            }
        }

        return $this->success(['id' => $brand->id], '创建成功');
    }

    /**
     * 更新品牌
     */
    public function update(int $id): Response
    {
        $brand = BrandModel::find($id);
        if (!$brand) {
            return $this->error('品牌不存在', 404);
        }

        $data = input('post.');
        $translations = $data['translations'] ?? [];

        $allowFields = ['name', 'logo', 'icon', 'sort', 'status'];

        foreach ($allowFields as $field) {
            if (isset($data[$field])) {
                $brand->$field = $data[$field];
            }
        }
        $brand->save();

        // 更新翻译
        foreach ($this->supportedLocales as $locale) {
            if (isset($translations[$locale])) {
                $brand->saveTranslation($locale, $translations[$locale]);
            }
        }

        return $this->success([], '更新成功');
    }

    /**
     * 删除品牌
     */
    public function delete(int $id): Response
    {
        $brand = BrandModel::find($id);
        if (!$brand) {
            return $this->error('品牌不存在', 404);
        }

        $brand->delete();
        return $this->success([], '删除成功');
    }

    /**
     * 获取所有启用的品牌（用于下拉选择）
     */
    public function all(): Response
    {
        $locale = input('locale', 'zh-tw');
        $list = BrandModel::getEnabledList($locale);
        return $this->success($list);
    }
}
