<?php
declare(strict_types=1);

namespace app\admin\controller;

use think\Response;
use app\common\model\CategoryAttribute as CategoryAttributeModel;
use app\common\model\CategoryAttributeTranslation;

/**
 * 品类属性管理控制器
 */
class CategoryAttribute extends Base
{
    /**
     * 属性列表
     * @return Response
     */
    public function index(): Response
    {
        [$page, $pageSize] = $this->getPageParams();

        $query = CategoryAttributeModel::order('sort', 'desc')->order('id', 'asc');

        // 搜索条件
        $categoryId = input('category_id', '');
        if ($categoryId !== '') {
            $query->where('category_id', (int) $categoryId);
        }

        $keyword = input('keyword', '');
        if ($keyword) {
            // 搜索属性名称或key
            $query->where(function ($q) use ($keyword) {
                $ids = CategoryAttributeTranslation::where('name', 'like', "%{$keyword}%")
                    ->column('attribute_id');
                $q->whereIn('id', $ids ?: [0])
                    ->whereOr('attr_key', 'like', "%{$keyword}%");
            });
        }

        $status = input('status', '');
        if ($status !== '') {
            $query->where('status', (int) $status);
        }

        $total = $query->count();
        $list = $query->page($page, $pageSize)->select()->toArray();

        // 获取所有翻译
        if ($list) {
            $ids = array_column($list, 'id');
            $translations = CategoryAttributeTranslation::whereIn('attribute_id', $ids)
                ->select()
                ->toArray();

            // 按属性ID分组翻译
            $translationMap = [];
            foreach ($translations as $t) {
                $translationMap[$t['attribute_id']][$t['locale']] = $t;
            }

            foreach ($list as &$item) {
                $item['translations'] = $translationMap[$item['id']] ?? [];
            }
        }

        return $this->paginate($list, $total, $page, $pageSize);
    }

    /**
     * 属性详情
     * @param int $id
     * @return Response
     */
    public function read(int $id): Response
    {
        $attribute = CategoryAttributeModel::find($id);
        if (!$attribute) {
            return $this->error('属性不存在', 404);
        }

        $data = $attribute->toArray();

        // 获取所有翻译
        $translations = CategoryAttributeTranslation::where('attribute_id', $id)
            ->select()
            ->toArray();

        $data['translations'] = [];
        foreach ($translations as $t) {
            $data['translations'][$t['locale']] = $t;
        }

        return $this->success($data);
    }

    /**
     * 创建属性
     * @return Response
     */
    public function create(): Response
    {
        $data = input('post.');

        // 验证必填字段 (category_id=0 表示全局属性，是有效值)
        if (!isset($data['category_id']) || $data['category_id'] === '' || $data['category_id'] === null) {
            return $this->error('请选择所属分类');
        }
        if (empty($data['attr_key'])) {
            return $this->error('请填写属性键名');
        }
        if (empty($data['input_type'])) {
            return $this->error('请选择输入类型');
        }
        if (empty($data['translations']) || !is_array($data['translations'])) {
            return $this->error('请提供至少一种语言的翻译');
        }

        // 检查attr_key是否重复
        $exists = CategoryAttributeModel::where('category_id', (int) $data['category_id'])
            ->where('attr_key', $data['attr_key'])
            ->find();
        if ($exists) {
            $errorMsg = (int) $data['category_id'] === 0 ? '全局属性中已存在相同的属性键名' : '该分类下已存在相同的属性键名';
            return $this->error($errorMsg);
        }

        // 创建属性
        $attribute = new CategoryAttributeModel();
        $attribute->category_id = $data['category_id'];
        $attribute->attr_key = $data['attr_key'];
        $attribute->input_type = $data['input_type'];
        $attribute->is_required = $data['is_required'] ?? 0;
        $attribute->parent_key = $data['parent_key'] ?? null;
        $attribute->sort = $data['sort'] ?? 0;
        $attribute->status = $data['status'] ?? 1;
        $attribute->save();

        // 保存翻译
        foreach ($data['translations'] as $locale => $trans) {
            if (!empty($trans['name'])) {
                $translation = new CategoryAttributeTranslation();
                $translation->attribute_id = $attribute->id;
                $translation->locale = $locale;
                $translation->name = $trans['name'];
                $translation->placeholder = $trans['placeholder'] ?? '';
                $translation->save();
            }
        }

        return $this->success(['id' => $attribute->id], '创建成功');
    }

    /**
     * 更新属性
     * @param int $id
     * @return Response
     */
    public function update(int $id): Response
    {
        $attribute = CategoryAttributeModel::find($id);
        if (!$attribute) {
            return $this->error('属性不存在', 404);
        }

        $data = input('post.');

        // 检查attr_key是否重复
        if (!empty($data['attr_key']) && $data['attr_key'] !== $attribute->attr_key) {
            $exists = CategoryAttributeModel::where('category_id', $attribute->category_id)
                ->where('attr_key', $data['attr_key'])
                ->where('id', '<>', $id)
                ->find();
            if ($exists) {
                return $this->error('该分类下已存在相同的属性键名');
            }
        }

        // 更新属性基本信息
        if (isset($data['attr_key'])) {
            $attribute->attr_key = $data['attr_key'];
        }
        if (isset($data['input_type'])) {
            $attribute->input_type = $data['input_type'];
        }
        if (isset($data['is_required'])) {
            $attribute->is_required = $data['is_required'];
        }
        if (array_key_exists('parent_key', $data)) {
            $attribute->parent_key = $data['parent_key'];
        }
        if (isset($data['sort'])) {
            $attribute->sort = $data['sort'];
        }
        if (isset($data['status'])) {
            $attribute->status = $data['status'];
        }
        $attribute->save();

        // 更新翻译
        if (!empty($data['translations']) && is_array($data['translations'])) {
            foreach ($data['translations'] as $locale => $trans) {
                $translation = CategoryAttributeTranslation::where('attribute_id', $id)
                    ->where('locale', $locale)
                    ->find();

                if (!$translation) {
                    $translation = new CategoryAttributeTranslation();
                    $translation->attribute_id = $id;
                    $translation->locale = $locale;
                }

                $translation->name = $trans['name'] ?? '';
                $translation->placeholder = $trans['placeholder'] ?? '';
                $translation->save();
            }
        }

        return $this->success([], '更新成功');
    }

    /**
     * 删除属性
     * @param int $id
     * @return Response
     */
    public function delete(int $id): Response
    {
        $attribute = CategoryAttributeModel::find($id);
        if (!$attribute) {
            return $this->error('属性不存在', 404);
        }

        // 删除翻译
        CategoryAttributeTranslation::where('attribute_id', $id)->delete();

        // 删除属性选项及其翻译
        $optionIds = \app\common\model\AttributeOption::where('attribute_id', $id)->column('id');
        if ($optionIds) {
            \app\common\model\AttributeOptionTranslation::whereIn('option_id', $optionIds)->delete();
            \app\common\model\AttributeOption::whereIn('id', $optionIds)->delete();
        }

        // 删除属性
        $attribute->delete();

        return $this->success([], '删除成功');
    }
}
