<?php
declare(strict_types=1);

namespace app\admin\controller;

use think\Response;
use app\common\model\AttributeOption as AttributeOptionModel;
use app\common\model\AttributeOptionTranslation;

/**
 * 属性选项管理控制器
 */
class AttributeOption extends Base
{
    /**
     * 选项列表
     * @return Response
     */
    public function index(): Response
    {
        [$page, $pageSize] = $this->getPageParams();

        $query = AttributeOptionModel::order('sort', 'desc')->order('id', 'asc');

        // 搜索条件
        $attributeId = input('attribute_id', '');
        if ($attributeId !== '') {
            $query->where('attribute_id', (int) $attributeId);
        }

        $keyword = input('keyword', '');
        if ($keyword) {
            // 搜索选项值或翻译标签
            $query->where(function ($q) use ($keyword) {
                $ids = AttributeOptionTranslation::where('label', 'like', "%{$keyword}%")
                    ->column('option_id');
                $q->whereIn('id', $ids ?: [0])
                    ->whereOr('option_value', 'like', "%{$keyword}%");
            });
        }

        $status = input('status', '');
        if ($status !== '') {
            $query->where('status', (int) $status);
        }

        // 父属性值筛选（如按品牌筛选型号）
        $parentValue = input('parent_value', '');
        if ($parentValue !== '') {
            $query->where('parent_value', $parentValue);
        }

        $total = $query->count();
        $list = $query->page($page, $pageSize)->select()->toArray();

        // 获取所有翻译
        if ($list) {
            $ids = array_column($list, 'id');
            $translations = AttributeOptionTranslation::whereIn('option_id', $ids)
                ->select()
                ->toArray();

            // 按选项ID分组翻译
            $translationMap = [];
            foreach ($translations as $t) {
                $translationMap[$t['option_id']][$t['locale']] = $t;
            }

            foreach ($list as &$item) {
                $item['translations'] = $translationMap[$item['id']] ?? [];
            }
        }

        return $this->paginate($list, $total, $page, $pageSize);
    }

    /**
     * 选项详情
     * @param int $id
     * @return Response
     */
    public function read(int $id): Response
    {
        $option = AttributeOptionModel::find($id);
        if (!$option) {
            return $this->error('选项不存在', 404);
        }

        $data = $option->toArray();

        // 获取所有翻译
        $translations = AttributeOptionTranslation::where('option_id', $id)
            ->select()
            ->toArray();

        $data['translations'] = [];
        foreach ($translations as $t) {
            $data['translations'][$t['locale']] = $t;
        }

        return $this->success($data);
    }

    /**
     * 创建选项
     * @return Response
     */
    public function create(): Response
    {
        $data = input('post.');

        // 验证必填字段
        if (empty($data['attribute_id'])) {
            return $this->error('请选择所属属性');
        }
        if (empty($data['option_value'])) {
            return $this->error('请填写选项值');
        }

        // 检查option_value是否重复
        $exists = AttributeOptionModel::where('attribute_id', $data['attribute_id'])
            ->where('option_value', $data['option_value'])
            ->find();
        if ($exists) {
            return $this->error('该属性下已存在相同的选项值');
        }

        // 创建选项
        $option = new AttributeOptionModel();
        $option->attribute_id = $data['attribute_id'];
        $option->option_value = $data['option_value'];
        $option->image = $data['image'] ?? null;
        $option->parent_value = $data['parent_value'] ?? null;
        $option->sort = $data['sort'] ?? 0;
        $option->status = $data['status'] ?? 1;
        $option->save();

        // 保存翻译
        if (!empty($data['translations']) && is_array($data['translations'])) {
            foreach ($data['translations'] as $locale => $trans) {
                if (!empty($trans['label'])) {
                    $translation = new AttributeOptionTranslation();
                    $translation->option_id = $option->id;
                    $translation->locale = $locale;
                    $translation->label = $trans['label'];
                    $translation->save();
                }
            }
        }

        return $this->success(['id' => $option->id], '创建成功');
    }

    /**
     * 更新选项
     * @param int $id
     * @return Response
     */
    public function update(int $id): Response
    {
        $option = AttributeOptionModel::find($id);
        if (!$option) {
            return $this->error('选项不存在', 404);
        }

        $data = input('post.');

        // 检查option_value是否重复
        if (!empty($data['option_value']) && $data['option_value'] !== $option->option_value) {
            $exists = AttributeOptionModel::where('attribute_id', $option->attribute_id)
                ->where('option_value', $data['option_value'])
                ->where('id', '<>', $id)
                ->find();
            if ($exists) {
                return $this->error('该属性下已存在相同的选项值');
            }
        }

        // 更新选项基本信息
        if (isset($data['option_value'])) {
            $option->option_value = $data['option_value'];
        }
        if (array_key_exists('image', $data)) {
            $option->image = $data['image'];
        }
        if (array_key_exists('parent_value', $data)) {
            $option->parent_value = $data['parent_value'];
        }
        if (isset($data['sort'])) {
            $option->sort = $data['sort'];
        }
        if (isset($data['status'])) {
            $option->status = $data['status'];
        }
        $option->save();

        // 更新翻译
        if (!empty($data['translations']) && is_array($data['translations'])) {
            foreach ($data['translations'] as $locale => $trans) {
                $translation = AttributeOptionTranslation::where('option_id', $id)
                    ->where('locale', $locale)
                    ->find();

                if (!$translation) {
                    $translation = new AttributeOptionTranslation();
                    $translation->option_id = $id;
                    $translation->locale = $locale;
                }

                $translation->label = $trans['label'] ?? '';
                $translation->save();
            }
        }

        return $this->success([], '更新成功');
    }

    /**
     * 删除选项
     * @param int $id
     * @return Response
     */
    public function delete(int $id): Response
    {
        $option = AttributeOptionModel::find($id);
        if (!$option) {
            return $this->error('选项不存在', 404);
        }

        // 删除翻译
        AttributeOptionTranslation::where('option_id', $id)->delete();

        // 删除选项
        $option->delete();

        return $this->success([], '删除成功');
    }

    /**
     * 批量创建选项
     * @return Response
     */
    public function batchCreate(): Response
    {
        $data = input('post.');

        if (empty($data['attribute_id'])) {
            return $this->error('请选择所属属性');
        }
        if (empty($data['options']) || !is_array($data['options'])) {
            return $this->error('请提供选项数据');
        }

        $attributeId = $data['attribute_id'];
        $createdIds = [];

        foreach ($data['options'] as $optionData) {
            if (empty($optionData['option_value'])) {
                continue;
            }

            // 检查是否重复
            $exists = AttributeOptionModel::where('attribute_id', $attributeId)
                ->where('option_value', $optionData['option_value'])
                ->find();
            if ($exists) {
                continue;
            }

            // 创建选项
            $option = new AttributeOptionModel();
            $option->attribute_id = $attributeId;
            $option->option_value = $optionData['option_value'];
            $option->image = $optionData['image'] ?? null;
            $option->parent_value = $optionData['parent_value'] ?? null;
            $option->sort = $optionData['sort'] ?? 0;
            $option->status = $optionData['status'] ?? 1;
            $option->save();

            // 保存翻译
            if (!empty($optionData['translations']) && is_array($optionData['translations'])) {
                foreach ($optionData['translations'] as $locale => $trans) {
                    if (!empty($trans['label'])) {
                        $translation = new AttributeOptionTranslation();
                        $translation->option_id = $option->id;
                        $translation->locale = $locale;
                        $translation->label = $trans['label'];
                        $translation->save();
                    }
                }
            }

            $createdIds[] = $option->id;
        }

        return $this->success(['ids' => $createdIds, 'count' => count($createdIds)], '批量创建成功');
    }

    /**
     * 批量删除选项
     * @return Response
     */
    public function batchDelete(): Response
    {
        $ids = input('post.ids', []);

        if (empty($ids) || !is_array($ids)) {
            return $this->error('请选择要删除的选项');
        }

        // 转换为整数数组
        $ids = array_map('intval', $ids);

        // 删除翻译
        AttributeOptionTranslation::whereIn('option_id', $ids)->delete();

        // 删除选项
        $deletedCount = AttributeOptionModel::whereIn('id', $ids)->delete();

        return $this->success(['count' => $deletedCount], '批量删除成功');
    }
}
