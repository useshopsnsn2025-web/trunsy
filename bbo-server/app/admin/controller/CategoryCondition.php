<?php
declare(strict_types=1);

namespace app\admin\controller;

use think\Response;
use app\common\model\CategoryConditionGroup;
use app\common\model\CategoryConditionGroupTranslation;
use app\common\model\CategoryConditionOption;
use app\common\model\CategoryConditionOptionTranslation;
use app\common\model\Category;

/**
 * 分类状态配置控制器
 */
class CategoryCondition extends Base
{
    /**
     * 获取状态组列表
     * @return Response
     */
    public function groups(): Response
    {
        $categoryId = input('category_id', 0, 'intval');

        $query = CategoryConditionGroup::order('sort', 'asc')->order('id', 'asc');

        if ($categoryId > 0) {
            $query->where('category_id', $categoryId);
        }

        $status = input('status', '');
        if ($status !== '') {
            $query->where('status', (int) $status);
        }

        $list = $query->select()->toArray();

        // 获取所有翻译
        if ($list) {
            $ids = array_column($list, 'id');
            $translations = CategoryConditionGroupTranslation::whereIn('group_id', $ids)
                ->select()
                ->toArray();

            // 按组ID分组翻译
            $translationMap = [];
            foreach ($translations as $t) {
                $translationMap[$t['group_id']][$t['locale']] = $t;
            }

            // 获取每个组的选项数量
            $optionCounts = CategoryConditionOption::whereIn('group_id', $ids)
                ->group('group_id')
                ->column('count(*)', 'group_id');

            foreach ($list as &$item) {
                $item['translations'] = $translationMap[$item['id']] ?? [];
                $item['option_count'] = $optionCounts[$item['id']] ?? 0;
            }
        }

        return $this->success($list);
    }

    /**
     * 获取状态组详情（含选项）
     * @param int $id
     * @return Response
     */
    public function groupDetail(int $id): Response
    {
        $group = CategoryConditionGroup::find($id);
        if (!$group) {
            return $this->error('状态组不存在', 404);
        }

        $data = $group->toArray();

        // 获取翻译
        $translations = CategoryConditionGroupTranslation::where('group_id', $id)
            ->select()
            ->toArray();
        $data['translations'] = [];
        foreach ($translations as $t) {
            $data['translations'][$t['locale']] = $t;
        }

        // 获取选项列表
        $options = CategoryConditionOption::where('group_id', $id)
            ->order('sort', 'asc')
            ->select()
            ->toArray();

        // 获取选项翻译
        if ($options) {
            $optionIds = array_column($options, 'id');
            $optionTranslations = CategoryConditionOptionTranslation::whereIn('option_id', $optionIds)
                ->select()
                ->toArray();

            $optionTransMap = [];
            foreach ($optionTranslations as $t) {
                $optionTransMap[$t['option_id']][$t['locale']] = $t;
            }

            foreach ($options as &$option) {
                $option['translations'] = $optionTransMap[$option['id']] ?? [];
            }
        }

        $data['options'] = $options;

        return $this->success($data);
    }

    /**
     * 创建状态组
     * @return Response
     */
    public function createGroup(): Response
    {
        $data = input('post.');

        // 验证分类
        if (empty($data['category_id'])) {
            return $this->error('请选择分类');
        }

        $category = Category::find($data['category_id']);
        if (!$category) {
            return $this->error('分类不存在');
        }

        // 验证翻译
        if (empty($data['translations']) || !is_array($data['translations'])) {
            return $this->error('请提供至少一种语言的名称');
        }

        // 获取默认名称
        $defaultName = $data['translations']['zh-tw']['name']
            ?? $data['translations']['en-us']['name']
            ?? array_values($data['translations'])[0]['name']
            ?? '';

        if (empty($defaultName)) {
            return $this->error('请提供状态组名称');
        }

        // 创建状态组
        $group = new CategoryConditionGroup();
        $group->category_id = $data['category_id'];
        $group->name = $defaultName;
        $group->icon = $data['icon'] ?? '';
        $group->sort = $data['sort'] ?? 0;
        $group->is_required = $data['is_required'] ?? 1;
        $group->status = $data['status'] ?? 1;
        $group->save();

        // 保存翻译
        foreach ($data['translations'] as $locale => $trans) {
            if (!empty($trans['name'])) {
                $translation = new CategoryConditionGroupTranslation();
                $translation->group_id = $group->id;
                $translation->locale = $locale;
                $translation->name = $trans['name'];
                $translation->save();
            }
        }

        return $this->success(['id' => $group->id], '创建成功');
    }

    /**
     * 更新状态组
     * @param int $id
     * @return Response
     */
    public function updateGroup(int $id): Response
    {
        $group = CategoryConditionGroup::find($id);
        if (!$group) {
            return $this->error('状态组不存在', 404);
        }

        $data = input('post.');

        // 更新基本信息
        if (isset($data['category_id'])) {
            $group->category_id = $data['category_id'];
        }
        if (isset($data['icon'])) {
            $group->icon = $data['icon'];
        }
        if (isset($data['sort'])) {
            $group->sort = $data['sort'];
        }
        if (isset($data['is_required'])) {
            $group->is_required = $data['is_required'];
        }
        if (isset($data['status'])) {
            $group->status = $data['status'];
        }

        // 更新默认名称
        if (!empty($data['translations'])) {
            $defaultName = $data['translations']['zh-tw']['name']
                ?? $data['translations']['en-us']['name']
                ?? array_values($data['translations'])[0]['name']
                ?? $group->name;
            $group->name = $defaultName;
        }

        $group->save();

        // 更新翻译
        if (!empty($data['translations']) && is_array($data['translations'])) {
            foreach ($data['translations'] as $locale => $trans) {
                $translation = CategoryConditionGroupTranslation::where('group_id', $id)
                    ->where('locale', $locale)
                    ->find();

                if (!$translation) {
                    $translation = new CategoryConditionGroupTranslation();
                    $translation->group_id = $id;
                    $translation->locale = $locale;
                }

                $translation->name = $trans['name'] ?? '';
                $translation->save();
            }
        }

        return $this->success([], '更新成功');
    }

    /**
     * 删除状态组
     * @param int $id
     * @return Response
     */
    public function deleteGroup(int $id): Response
    {
        $group = CategoryConditionGroup::find($id);
        if (!$group) {
            return $this->error('状态组不存在', 404);
        }

        // 删除相关选项翻译
        $optionIds = CategoryConditionOption::where('group_id', $id)->column('id');
        if ($optionIds) {
            CategoryConditionOptionTranslation::whereIn('option_id', $optionIds)->delete();
        }

        // 删除选项
        CategoryConditionOption::where('group_id', $id)->delete();

        // 删除组翻译
        CategoryConditionGroupTranslation::where('group_id', $id)->delete();

        // 删除组
        $group->delete();

        return $this->success([], '删除成功');
    }

    /**
     * 获取选项列表
     * @return Response
     */
    public function options(): Response
    {
        $groupId = input('group_id', 0, 'intval');

        if ($groupId <= 0) {
            return $this->error('请指定状态组');
        }

        $options = CategoryConditionOption::where('group_id', $groupId)
            ->order('sort', 'asc')
            ->select()
            ->toArray();

        // 获取翻译
        if ($options) {
            $ids = array_column($options, 'id');
            $translations = CategoryConditionOptionTranslation::whereIn('option_id', $ids)
                ->select()
                ->toArray();

            $translationMap = [];
            foreach ($translations as $t) {
                $translationMap[$t['option_id']][$t['locale']] = $t;
            }

            foreach ($options as &$option) {
                $option['translations'] = $translationMap[$option['id']] ?? [];
            }
        }

        return $this->success($options);
    }

    /**
     * 创建选项
     * @return Response
     */
    public function createOption(): Response
    {
        $data = input('post.');

        // 验证状态组
        if (empty($data['group_id'])) {
            return $this->error('请指定状态组');
        }

        $group = CategoryConditionGroup::find($data['group_id']);
        if (!$group) {
            return $this->error('状态组不存在');
        }

        // 验证翻译
        if (empty($data['translations']) || !is_array($data['translations'])) {
            return $this->error('请提供至少一种语言的名称');
        }

        // 获取默认名称
        $defaultName = $data['translations']['zh-tw']['name']
            ?? $data['translations']['en-us']['name']
            ?? array_values($data['translations'])[0]['name']
            ?? '';

        if (empty($defaultName)) {
            return $this->error('请提供选项名称');
        }

        // 创建选项
        $option = new CategoryConditionOption();
        $option->group_id = $data['group_id'];
        $option->name = $defaultName;
        $option->sort = $data['sort'] ?? 0;
        $option->impact_level = $data['impact_level'] ?? null;
        $option->status = $data['status'] ?? 1;
        $option->save();

        // 保存翻译
        foreach ($data['translations'] as $locale => $trans) {
            if (!empty($trans['name'])) {
                $translation = new CategoryConditionOptionTranslation();
                $translation->option_id = $option->id;
                $translation->locale = $locale;
                $translation->name = $trans['name'];
                $translation->save();
            }
        }

        return $this->success(['id' => $option->id], '创建成功');
    }

    /**
     * 更新选项
     * @param int $id
     * @return Response
     */
    public function updateOption(int $id): Response
    {
        $option = CategoryConditionOption::find($id);
        if (!$option) {
            return $this->error('选项不存在', 404);
        }

        $data = input('post.');

        // 更新基本信息
        if (isset($data['sort'])) {
            $option->sort = $data['sort'];
        }
        if (isset($data['impact_level'])) {
            $option->impact_level = $data['impact_level'];
        }
        if (isset($data['status'])) {
            $option->status = $data['status'];
        }

        // 更新默认名称
        if (!empty($data['translations'])) {
            $defaultName = $data['translations']['zh-tw']['name']
                ?? $data['translations']['en-us']['name']
                ?? array_values($data['translations'])[0]['name']
                ?? $option->name;
            $option->name = $defaultName;
        }

        $option->save();

        // 更新翻译
        if (!empty($data['translations']) && is_array($data['translations'])) {
            foreach ($data['translations'] as $locale => $trans) {
                $translation = CategoryConditionOptionTranslation::where('option_id', $id)
                    ->where('locale', $locale)
                    ->find();

                if (!$translation) {
                    $translation = new CategoryConditionOptionTranslation();
                    $translation->option_id = $id;
                    $translation->locale = $locale;
                }

                $translation->name = $trans['name'] ?? '';
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
    public function deleteOption(int $id): Response
    {
        $option = CategoryConditionOption::find($id);
        if (!$option) {
            return $this->error('选项不存在', 404);
        }

        // 删除翻译
        CategoryConditionOptionTranslation::where('option_id', $id)->delete();

        // 删除选项
        $option->delete();

        return $this->success([], '删除成功');
    }

    /**
     * 批量更新选项排序
     * @return Response
     */
    public function sortOptions(): Response
    {
        $items = input('post.items', []);

        if (empty($items) || !is_array($items)) {
            return $this->error('请提供排序数据');
        }

        foreach ($items as $item) {
            if (!empty($item['id']) && isset($item['sort'])) {
                CategoryConditionOption::where('id', $item['id'])
                    ->update(['sort' => $item['sort']]);
            }
        }

        return $this->success([], '排序更新成功');
    }

    /**
     * 批量更新状态组排序
     * @return Response
     */
    public function sortGroups(): Response
    {
        $items = input('post.items', []);

        if (empty($items) || !is_array($items)) {
            return $this->error('请提供排序数据');
        }

        foreach ($items as $item) {
            if (!empty($item['id']) && isset($item['sort'])) {
                CategoryConditionGroup::where('id', $item['id'])
                    ->update(['sort' => $item['sort']]);
            }
        }

        return $this->success([], '排序更新成功');
    }

    /**
     * 获取分类的状态组（含选项）- 用于商品表单
     * @return Response
     */
    public function groupsWithOptions(): Response
    {
        $categoryId = input('category_id', 0, 'intval');

        if ($categoryId <= 0) {
            return $this->success([]);
        }

        // 获取启用的状态组
        $groups = CategoryConditionGroup::where('category_id', $categoryId)
            ->where('status', 1)
            ->order('sort', 'asc')
            ->select()
            ->toArray();

        if (empty($groups)) {
            return $this->success([]);
        }

        $groupIds = array_column($groups, 'id');

        // 获取组翻译
        $groupTranslations = CategoryConditionGroupTranslation::whereIn('group_id', $groupIds)
            ->select()
            ->toArray();
        $groupTransMap = [];
        foreach ($groupTranslations as $t) {
            $groupTransMap[$t['group_id']][$t['locale']] = $t;
        }

        // 获取所有启用的选项
        $options = CategoryConditionOption::whereIn('group_id', $groupIds)
            ->where('status', 1)
            ->order('sort', 'asc')
            ->select()
            ->toArray();

        // 获取选项翻译
        $optionIds = array_column($options, 'id');
        $optionTranslations = [];
        if ($optionIds) {
            $optionTranslations = CategoryConditionOptionTranslation::whereIn('option_id', $optionIds)
                ->select()
                ->toArray();
        }
        $optionTransMap = [];
        foreach ($optionTranslations as $t) {
            $optionTransMap[$t['option_id']][$t['locale']] = $t;
        }

        // 按组分配选项
        $optionsByGroup = [];
        foreach ($options as $option) {
            $option['translations'] = $optionTransMap[$option['id']] ?? [];
            $optionsByGroup[$option['group_id']][] = $option;
        }

        // 组装结果
        foreach ($groups as &$group) {
            $group['translations'] = $groupTransMap[$group['id']] ?? [];
            $group['options'] = $optionsByGroup[$group['id']] ?? [];
        }

        return $this->success($groups);
    }

    /**
     * 复制状态配置到其他分类
     * @return Response
     */
    public function copyToCategory(): Response
    {
        $sourceCategoryId = input('post.source_category_id', 0, 'intval');
        $targetCategoryId = input('post.target_category_id', 0, 'intval');

        if ($sourceCategoryId <= 0 || $targetCategoryId <= 0) {
            return $this->error('请指定源分类和目标分类');
        }

        if ($sourceCategoryId === $targetCategoryId) {
            return $this->error('源分类和目标分类不能相同');
        }

        // 获取源分类的状态组
        $sourceGroups = CategoryConditionGroup::where('category_id', $sourceCategoryId)
            ->select()
            ->toArray();

        if (empty($sourceGroups)) {
            return $this->error('源分类没有状态配置');
        }

        // 复制每个状态组
        foreach ($sourceGroups as $sourceGroup) {
            // 创建新组
            $newGroup = new CategoryConditionGroup();
            $newGroup->category_id = $targetCategoryId;
            $newGroup->name = $sourceGroup['name'];
            $newGroup->icon = $sourceGroup['icon'];
            $newGroup->sort = $sourceGroup['sort'];
            $newGroup->is_required = $sourceGroup['is_required'];
            $newGroup->status = $sourceGroup['status'];
            $newGroup->save();

            // 复制组翻译
            $groupTranslations = CategoryConditionGroupTranslation::where('group_id', $sourceGroup['id'])
                ->select();
            foreach ($groupTranslations as $trans) {
                $newTrans = new CategoryConditionGroupTranslation();
                $newTrans->group_id = $newGroup->id;
                $newTrans->locale = $trans->locale;
                $newTrans->name = $trans->name;
                $newTrans->save();
            }

            // 复制选项
            $sourceOptions = CategoryConditionOption::where('group_id', $sourceGroup['id'])
                ->select();
            foreach ($sourceOptions as $sourceOption) {
                $newOption = new CategoryConditionOption();
                $newOption->group_id = $newGroup->id;
                $newOption->name = $sourceOption->name;
                $newOption->sort = $sourceOption->sort;
                $newOption->impact_level = $sourceOption->impact_level;
                $newOption->status = $sourceOption->status;
                $newOption->save();

                // 复制选项翻译
                $optionTranslations = CategoryConditionOptionTranslation::where('option_id', $sourceOption->id)
                    ->select();
                foreach ($optionTranslations as $trans) {
                    $newTrans = new CategoryConditionOptionTranslation();
                    $newTrans->option_id = $newOption->id;
                    $newTrans->locale = $trans->locale;
                    $newTrans->name = $trans->name;
                    $newTrans->save();
                }
            }
        }

        return $this->success([], '复制成功');
    }
}
