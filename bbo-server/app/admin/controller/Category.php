<?php
declare(strict_types=1);

namespace app\admin\controller;

use think\Response;
use app\common\model\Category as CategoryModel;
use app\common\model\CategoryTranslation;
use app\common\model\CategoryAttribute;

/**
 * 分类管理控制器
 */
class Category extends Base
{
    /**
     * 分类列表
     * @return Response
     */
    public function index(): Response
    {
        [$page, $pageSize] = $this->getPageParams();

        $query = CategoryModel::order('sort', 'desc')->order('id', 'asc');

        // 搜索条件
        $keyword = input('keyword', '');
        if ($keyword) {
            // 搜索分类名称（需要联表查询翻译表）
            $ids = CategoryTranslation::where('name', 'like', "%{$keyword}%")
                ->column('category_id');
            if ($ids) {
                $query->whereIn('id', $ids);
            } else {
                return $this->paginate([], 0, $page, $pageSize);
            }
        }

        $parentId = input('parent_id', '');
        if ($parentId !== '') {
            $query->where('parent_id', (int) $parentId);
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
            $translations = CategoryTranslation::whereIn('category_id', $ids)
                ->select()
                ->toArray();

            // 按分类ID分组翻译
            $translationMap = [];
            foreach ($translations as $t) {
                $translationMap[$t['category_id']][$t['locale']] = $t;
            }

            foreach ($list as &$item) {
                $item['translations'] = $translationMap[$item['id']] ?? [];
            }
        }

        return $this->paginate($list, $total, $page, $pageSize);
    }

    /**
     * 分类详情
     * @param int $id
     * @return Response
     */
    public function read(int $id): Response
    {
        $category = CategoryModel::find($id);
        if (!$category) {
            return $this->error('分类不存在', 404);
        }

        $data = $category->toArray();

        // 获取所有翻译
        $translations = CategoryTranslation::where('category_id', $id)
            ->select()
            ->toArray();

        $data['translations'] = [];
        foreach ($translations as $t) {
            $data['translations'][$t['locale']] = $t;
        }

        return $this->success($data);
    }

    /**
     * 创建分类
     * @return Response
     */
    public function create(): Response
    {
        $data = input('post.');

        // 验证必填字段
        if (empty($data['translations']) || !is_array($data['translations'])) {
            return $this->error('请提供至少一种语言的翻译');
        }

        // 创建分类
        $category = new CategoryModel();
        $category->parent_id = $data['parent_id'] ?? 0;
        $category->icon = $data['icon'] ?? '';
        $category->sort = $data['sort'] ?? 0;
        $category->status = $data['status'] ?? 1;
        $category->is_hot = $data['is_hot'] ?? 0;
        $category->save();

        // 保存翻译
        foreach ($data['translations'] as $locale => $trans) {
            if (!empty($trans['name'])) {
                $translation = new CategoryTranslation();
                $translation->category_id = $category->id;
                $translation->locale = $locale;
                $translation->name = $trans['name'];
                $translation->description = $trans['description'] ?? '';
                $translation->save();
            }
        }

        return $this->success(['id' => $category->id], '创建成功');
    }

    /**
     * 更新分类
     * @param int $id
     * @return Response
     */
    public function update(int $id): Response
    {
        $category = CategoryModel::find($id);
        if (!$category) {
            return $this->error('分类不存在', 404);
        }

        $data = input('post.');

        // 更新分类基本信息
        if (isset($data['parent_id'])) {
            $category->parent_id = $data['parent_id'];
        }
        if (isset($data['icon'])) {
            $category->icon = $data['icon'];
        }
        if (isset($data['sort'])) {
            $category->sort = $data['sort'];
        }
        if (isset($data['status'])) {
            $category->status = $data['status'];
        }
        if (isset($data['is_hot'])) {
            $category->is_hot = $data['is_hot'];
        }
        $category->save();

        // 更新翻译
        if (!empty($data['translations']) && is_array($data['translations'])) {
            foreach ($data['translations'] as $locale => $trans) {
                $translation = CategoryTranslation::where('category_id', $id)
                    ->where('locale', $locale)
                    ->find();

                if (!$translation) {
                    $translation = new CategoryTranslation();
                    $translation->category_id = $id;
                    $translation->locale = $locale;
                }

                $translation->name = $trans['name'] ?? '';
                $translation->description = $trans['description'] ?? '';
                $translation->save();
            }
        }

        return $this->success([], '更新成功');
    }

    /**
     * 删除分类
     * @param int $id
     * @return Response
     */
    public function delete(int $id): Response
    {
        $category = CategoryModel::find($id);
        if (!$category) {
            return $this->error('分类不存在', 404);
        }

        // 检查是否有子分类
        $childCount = CategoryModel::where('parent_id', $id)->count();
        if ($childCount > 0) {
            return $this->error('该分类下有子分类，无法删除');
        }

        // 删除翻译
        CategoryTranslation::where('category_id', $id)->delete();

        // 删除分类
        $category->delete();

        return $this->success([], '删除成功');
    }

    /**
     * 获取分类树
     * @return Response
     */
    public function tree(): Response
    {
        $categories = CategoryModel::order('sort', 'desc')
            ->order('id', 'asc')
            ->select()
            ->toArray();

        // 获取所有翻译
        $ids = array_column($categories, 'id');
        $translations = CategoryTranslation::whereIn('category_id', $ids)
            ->select()
            ->toArray();

        // 按分类ID分组翻译
        $translationMap = [];
        foreach ($translations as $t) {
            $translationMap[$t['category_id']][$t['locale']] = $t;
        }

        foreach ($categories as &$item) {
            $item['translations'] = $translationMap[$item['id']] ?? [];
        }

        // 构建树形结构
        $tree = $this->buildTree($categories, 0);

        return $this->success($tree);
    }

    /**
     * 构建树形结构
     * @param array $items
     * @param int $parentId
     * @return array
     */
    private function buildTree(array $items, int $parentId): array
    {
        $result = [];
        foreach ($items as $item) {
            if ($item['parent_id'] == $parentId) {
                $children = $this->buildTree($items, $item['id']);
                if ($children) {
                    $item['children'] = $children;
                }
                $result[] = $item;
            }
        }
        return $result;
    }

    /**
     * 获取分类属性（用于商品表单动态属性）
     * @param int $id 分类ID
     * @return Response
     */
    public function attributes(int $id): Response
    {
        // 检查分类是否存在
        $category = CategoryModel::find($id);
        if (!$category) {
            return $this->error('分类不存在', 404);
        }

        $locale = input('locale', 'zh-tw');
        $attributes = CategoryAttribute::getAttributesByCategoryId($id, $locale);

        return $this->success($attributes);
    }
}
