<?php

namespace app\admin\controller;

use app\common\model\SellFaq as SellFaqModel;
use app\common\model\SellFaqTranslation;
use think\facade\Db;

/**
 * 出售常见问题管理
 */
class SellFaq extends Base
{
    /**
     * FAQ列表
     */
    public function list()
    {
        $page = request()->get('page', 1);
        $pageSize = request()->get('pageSize', 20);
        $status = request()->get('status', '');

        $query = SellFaqModel::order('sort_order', 'desc')->order('id', 'asc');

        if ($status !== '') {
            $query->where('status', (int)$status);
        }

        $total = $query->count();
        $list = $query->page($page, $pageSize)->select()->toArray();

        // 获取所有翻译
        $faqIds = array_column($list, 'id');
        $translations = [];
        if (!empty($faqIds)) {
            $transData = SellFaqTranslation::whereIn('faq_id', $faqIds)->select()->toArray();
            foreach ($transData as $trans) {
                if (!isset($translations[$trans['faq_id']])) {
                    $translations[$trans['faq_id']] = [];
                }
                $translations[$trans['faq_id']][$trans['locale']] = [
                    'question' => $trans['question'],
                    'answer' => $trans['answer'],
                ];
            }
        }

        // 组装数据
        foreach ($list as &$item) {
            $item['translations'] = $translations[$item['id']] ?? [];
        }

        return $this->success([
            'list' => $list,
            'total' => $total,
            'page' => (int)$page,
            'pageSize' => (int)$pageSize,
        ]);
    }

    /**
     * FAQ详情
     */
    public function detail()
    {
        $id = request()->get('id');
        if (!$id) {
            return $this->error('Missing id');
        }

        $faq = SellFaqModel::find($id);
        if (!$faq) {
            return $this->error('FAQ not found');
        }

        $data = $faq->toArray();

        // 获取翻译
        $transData = SellFaqTranslation::where('faq_id', $id)->select()->toArray();
        $translations = [];
        foreach ($transData as $trans) {
            $translations[$trans['locale']] = [
                'question' => $trans['question'],
                'answer' => $trans['answer'],
            ];
        }
        $data['translations'] = $translations;

        return $this->success($data);
    }

    /**
     * 创建FAQ
     */
    public function create()
    {
        $sortOrder = request()->post('sort_order', 0);
        $status = request()->post('status', 1);
        $translations = request()->post('translations', []);

        if (empty($translations)) {
            return $this->error('Translations are required');
        }

        // 验证至少有一个语言的翻译
        $hasValidTranslation = false;
        foreach ($translations as $locale => $trans) {
            if (!empty($trans['question']) && !empty($trans['answer'])) {
                $hasValidTranslation = true;
                break;
            }
        }

        if (!$hasValidTranslation) {
            return $this->error('At least one language translation is required');
        }

        Db::startTrans();
        try {
            // 创建FAQ
            $faq = new SellFaqModel();
            $faq->sort_order = (int)$sortOrder;
            $faq->status = (int)$status;
            $faq->save();

            // 创建翻译
            foreach ($translations as $locale => $trans) {
                if (!empty($trans['question']) && !empty($trans['answer'])) {
                    $translation = new SellFaqTranslation();
                    $translation->faq_id = $faq->id;
                    $translation->locale = $locale;
                    $translation->question = $trans['question'];
                    $translation->answer = $trans['answer'];
                    $translation->save();
                }
            }

            Db::commit();
            return $this->success(['id' => $faq->id], 'Created successfully');

        } catch (\Exception $e) {
            Db::rollback();
            return $this->error('Create failed: ' . $e->getMessage());
        }
    }

    /**
     * 更新FAQ
     */
    public function update()
    {
        $id = request()->post('id');
        if (!$id) {
            return $this->error('Missing id');
        }

        $faq = SellFaqModel::find($id);
        if (!$faq) {
            return $this->error('FAQ not found');
        }

        $sortOrder = request()->post('sort_order');
        $status = request()->post('status');
        $translations = request()->post('translations', []);

        Db::startTrans();
        try {
            // 更新FAQ
            if ($sortOrder !== null) {
                $faq->sort_order = (int)$sortOrder;
            }
            if ($status !== null) {
                $faq->status = (int)$status;
            }
            $faq->save();

            // 更新翻译
            if (!empty($translations)) {
                foreach ($translations as $locale => $trans) {
                    if (!empty($trans['question']) && !empty($trans['answer'])) {
                        $existing = SellFaqTranslation::where('faq_id', $id)
                            ->where('locale', $locale)
                            ->find();

                        if ($existing) {
                            $existing->question = $trans['question'];
                            $existing->answer = $trans['answer'];
                            $existing->save();
                        } else {
                            $translation = new SellFaqTranslation();
                            $translation->faq_id = $id;
                            $translation->locale = $locale;
                            $translation->question = $trans['question'];
                            $translation->answer = $trans['answer'];
                            $translation->save();
                        }
                    }
                }
            }

            Db::commit();
            return $this->success(null, 'Updated successfully');

        } catch (\Exception $e) {
            Db::rollback();
            return $this->error('Update failed: ' . $e->getMessage());
        }
    }

    /**
     * 删除FAQ
     */
    public function delete()
    {
        $id = request()->post('id');
        if (!$id) {
            return $this->error('Missing id');
        }

        $faq = SellFaqModel::find($id);
        if (!$faq) {
            return $this->error('FAQ not found');
        }

        Db::startTrans();
        try {
            // 删除翻译（由于外键约束，也可以自动删除）
            SellFaqTranslation::where('faq_id', $id)->delete();
            // 删除FAQ
            $faq->delete();

            Db::commit();
            return $this->success(null, 'Deleted successfully');

        } catch (\Exception $e) {
            Db::rollback();
            return $this->error('Delete failed: ' . $e->getMessage());
        }
    }

    /**
     * 更新状态
     */
    public function updateStatus()
    {
        $id = request()->post('id');
        $status = request()->post('status');

        if (!$id || $status === null) {
            return $this->error('Missing parameters');
        }

        $faq = SellFaqModel::find($id);
        if (!$faq) {
            return $this->error('FAQ not found');
        }

        $faq->status = (int)$status;
        $faq->save();

        return $this->success(null, 'Status updated');
    }

    /**
     * 批量更新排序
     */
    public function updateSort()
    {
        $items = request()->post('items', []);

        if (empty($items)) {
            return $this->error('No items to update');
        }

        Db::startTrans();
        try {
            foreach ($items as $item) {
                if (isset($item['id']) && isset($item['sort_order'])) {
                    SellFaqModel::where('id', $item['id'])
                        ->update(['sort_order' => (int)$item['sort_order']]);
                }
            }

            Db::commit();
            return $this->success(null, 'Sort order updated');

        } catch (\Exception $e) {
            Db::rollback();
            return $this->error('Update failed: ' . $e->getMessage());
        }
    }
}
