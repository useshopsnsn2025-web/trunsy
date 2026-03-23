<?php
declare(strict_types=1);

namespace app\admin\controller;

use think\Response;
use app\common\model\QuickReply as QuickReplyModel;
use app\common\model\QuickReplyTranslation;
use app\common\model\QuickReplyGroup;

/**
 * 快捷回复管理控制器
 */
class QuickReply extends Base
{
    /**
     * 快捷回复列表
     */
    public function index(): Response
    {
        [$page, $pageSize] = $this->getPageParams();
        $locale = input('locale', 'zh-tw');

        $query = QuickReplyModel::order('sort', 'desc')->order('id', 'desc');

        $groupId = input('group_id', '');
        if ($groupId !== '') {
            $query->where('group_id', (int)$groupId);
        }

        $keyword = input('keyword', '');
        if ($keyword) {
            $query->where(function ($q) use ($keyword) {
                $q->where('title', 'like', "%{$keyword}%")
                  ->whereOr('content', 'like', "%{$keyword}%");
            });
        }

        $isEnabled = input('is_enabled', '');
        if ($isEnabled !== '') {
            $query->where('is_enabled', (int)$isEnabled);
        }

        $total = $query->count();
        $replies = $query->with(['group', 'translations'])->page($page, $pageSize)->select();

        // 应用本地化
        $list = [];
        foreach ($replies as $reply) {
            $item = $reply->toArray();
            $item['title'] = $reply->getLocalizedTitle($locale);
            $item['content'] = $reply->getLocalizedContent($locale);
            $list[] = $item;
        }

        return $this->paginate($list, $total, $page, $pageSize);
    }

    /**
     * 快捷回复详情
     */
    public function read(int $id): Response
    {
        $reply = QuickReplyModel::with(['group', 'translations'])->find($id);
        if (!$reply) {
            return $this->error('快捷回复不存在', 404);
        }
        return $this->success($reply->toArray());
    }

    /**
     * 创建快捷回复
     */
    public function create(): Response
    {
        $data = input('post.');

        // 验证翻译数据
        $translations = $data['translations'] ?? [];
        // 支持 zh-tw 或 zh-cn 作为繁体/简体中文
        $zhTitle = $translations['zh-tw']['title'] ?? ($translations['zh-cn']['title'] ?? '');
        $zhContent = $translations['zh-tw']['content'] ?? ($translations['zh-cn']['content'] ?? '');
        if (empty($zhTitle)) {
            return $this->error('請填寫模板標題（繁體中文）');
        }
        if (empty($zhContent)) {
            return $this->error('請填寫模板內容（繁體中文）');
        }

        $reply = new QuickReplyModel();
        $reply->group_id = $data['group_id'] ?? null;
        $reply->service_id = $data['service_id'] ?? null;
        $reply->title = $zhTitle; // 保持title字段同步
        $reply->content = $zhContent; // 保持content字段同步
        $reply->shortcut = $data['shortcut'] ?? null;
        $reply->sort = $data['sort'] ?? 0;
        $reply->is_enabled = $data['is_enabled'] ?? 1;
        $reply->save();

        // 保存翻译
        $this->saveTranslations($reply->id, $translations);

        return $this->success(['id' => $reply->id], '创建成功');
    }

    /**
     * 更新快捷回复
     */
    public function update(int $id): Response
    {
        $reply = QuickReplyModel::find($id);
        if (!$reply) {
            return $this->error('快捷回复不存在', 404);
        }

        $data = input('post.');

        // 更新翻译
        if (isset($data['translations']) && is_array($data['translations'])) {
            $this->saveTranslations($id, $data['translations']);
            // 同步繁体中文标题和内容到主表字段（优先 zh-tw，降级 zh-cn）
            $zhTitle = $data['translations']['zh-tw']['title'] ?? ($data['translations']['zh-cn']['title'] ?? '');
            $zhContent = $data['translations']['zh-tw']['content'] ?? ($data['translations']['zh-cn']['content'] ?? '');
            if (!empty($zhTitle)) {
                $reply->title = $zhTitle;
            }
            if (!empty($zhContent)) {
                $reply->content = $zhContent;
            }
        }

        if (isset($data['group_id'])) {
            $reply->group_id = $data['group_id'] ?: null;
        }
        if (isset($data['shortcut'])) {
            $reply->shortcut = $data['shortcut'];
        }
        if (isset($data['sort'])) {
            $reply->sort = $data['sort'];
        }
        if (isset($data['is_enabled'])) {
            $reply->is_enabled = $data['is_enabled'];
        }
        $reply->save();

        return $this->success([], '更新成功');
    }

    /**
     * 删除快捷回复
     */
    public function delete(int $id): Response
    {
        $reply = QuickReplyModel::find($id);
        if (!$reply) {
            return $this->error('快捷回复不存在', 404);
        }

        // 删除翻译
        QuickReplyTranslation::where('reply_id', $id)->delete();
        $reply->delete();

        return $this->success([], '删除成功');
    }

    /**
     * 获取分组列表
     */
    public function groups(): Response
    {
        $list = QuickReplyGroup::order('sort', 'asc')
            ->order('id', 'asc')
            ->select()
            ->toArray();

        return $this->success($list);
    }

    /**
     * 创建分组
     */
    public function createGroup(): Response
    {
        $name = input('post.name', '');
        if (empty($name)) {
            return $this->error('请填写分组名称');
        }

        $group = new QuickReplyGroup();
        $group->name = $name;
        $group->sort = input('post.sort', 0);
        $group->save();

        return $this->success(['id' => $group->id], '创建成功');
    }

    /**
     * 更新分组
     */
    public function updateGroup(int $id): Response
    {
        $group = QuickReplyGroup::find($id);
        if (!$group) {
            return $this->error('分组不存在', 404);
        }

        $data = input('post.');
        if (isset($data['name'])) {
            $group->name = $data['name'];
        }
        if (isset($data['sort'])) {
            $group->sort = $data['sort'];
        }
        $group->save();

        return $this->success([], '更新成功');
    }

    /**
     * 删除分组
     */
    public function deleteGroup(int $id): Response
    {
        $group = QuickReplyGroup::find($id);
        if (!$group) {
            return $this->error('分组不存在', 404);
        }

        // 将该分组下的回复移动到未分组
        QuickReplyModel::where('group_id', $id)->update(['group_id' => null]);

        $group->delete();

        return $this->success([], '删除成功');
    }

    /**
     * 获取所有快捷回复（按分组）
     */
    public function all(): Response
    {
        $locale = input('locale', 'zh-tw');

        $groups = QuickReplyGroup::order('sort', 'asc')
            ->order('id', 'asc')
            ->select()
            ->toArray();

        $replyModels = QuickReplyModel::where('is_enabled', 1)
            ->order('sort', 'desc')
            ->order('id', 'asc')
            ->select();

        // 应用本地化
        $replies = [];
        foreach ($replyModels as $reply) {
            $item = $reply->toArray();
            $item['title'] = $reply->getLocalizedTitle($locale);
            $item['content'] = $reply->getLocalizedContent($locale);
            $replies[] = $item;
        }

        // 按分组整理
        $result = [];
        foreach ($groups as $group) {
            $group['replies'] = array_filter($replies, function ($r) use ($group) {
                return $r['group_id'] == $group['id'];
            });
            $group['replies'] = array_values($group['replies']);
            $result[] = $group;
        }

        // 未分组的
        $ungrouped = array_filter($replies, function ($r) {
            return empty($r['group_id']);
        });
        if (!empty($ungrouped)) {
            $result[] = [
                'id' => 0,
                'name' => '未分组',
                'replies' => array_values($ungrouped),
            ];
        }

        return $this->success($result);
    }

    /**
     * 保存翻译数据
     * @param int $replyId 快捷回复ID
     * @param array $translations 翻译数据 ['zh-cn' => ['title' => '...', 'content' => '...'], ...]
     */
    protected function saveTranslations(int $replyId, array $translations): void
    {
        foreach ($translations as $locale => $data) {
            if (empty($data['title']) || empty($data['content'])) {
                continue;
            }

            $translation = QuickReplyTranslation::where('reply_id', $replyId)
                ->where('locale', $locale)
                ->find();

            if ($translation) {
                $translation->title = $data['title'];
                $translation->content = $data['content'];
                $translation->save();
            } else {
                $newTranslation = new QuickReplyTranslation();
                $newTranslation->reply_id = $replyId;
                $newTranslation->locale = $locale;
                $newTranslation->title = $data['title'];
                $newTranslation->content = $data['content'];
                $newTranslation->save();
            }
        }
    }
}
