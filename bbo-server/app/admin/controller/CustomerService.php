<?php
declare(strict_types=1);

namespace app\admin\controller;

use think\Response;
use app\common\model\CustomerService as ServiceModel;
use app\common\model\CustomerServiceTranslation;
use app\common\model\ServiceGroup;

/**
 * 客服人员管理控制器
 */
class CustomerService extends Base
{
    /**
     * 客服列表
     */
    public function index(): Response
    {
        [$page, $pageSize] = $this->getPageParams();

        $query = ServiceModel::order('id', 'desc');

        $keyword = input('keyword', '');
        if ($keyword) {
            $query->where(function ($q) use ($keyword) {
                $q->where('name', 'like', "%{$keyword}%")
                  ->whereOr('email', 'like', "%{$keyword}%")
                  ->whereOr('phone', 'like', "%{$keyword}%");
            });
        }

        $status = input('status', '');
        if ($status !== '') {
            $query->where('status', (int)$status);
        }

        $isEnabled = input('is_enabled', '');
        if ($isEnabled !== '') {
            $query->where('is_enabled', (int)$isEnabled);
        }

        $total = $query->count();
        $list = $query->with(['admin', 'translations'])->page($page, $pageSize)->select()->toArray();

        return $this->paginate($list, $total, $page, $pageSize);
    }

    /**
     * 客服详情
     */
    public function read(int $id): Response
    {
        $service = ServiceModel::with(['admin', 'groups', 'translations'])->find($id);
        if (!$service) {
            return $this->error('客服不存在', 404);
        }
        return $this->success($service->toArray());
    }

    /**
     * 创建客服
     */
    public function create(): Response
    {
        $data = input('post.');

        // 验证翻译数据
        $translations = $data['translations'] ?? [];
        // 支持 zh-tw 或 zh-cn 作为主语言
        $primaryName = $translations['zh-tw']['name'] ?? $translations['zh-cn']['name'] ?? '';
        if (empty($primaryName)) {
            return $this->error('请填写客服名称（中文）');
        }

        $service = new ServiceModel();
        $service->name = $primaryName; // 保持name字段同步
        $service->admin_id = $data['admin_id'] ?? null;
        $service->avatar = $data['avatar'] ?? null;
        $service->email = $data['email'] ?? null;
        $service->phone = $data['phone'] ?? null;
        $service->max_sessions = $data['max_sessions'] ?? 10;
        $service->status = ServiceModel::STATUS_OFFLINE;
        $service->is_enabled = $data['is_enabled'] ?? 1;
        $service->save();

        // 保存翻译
        $this->saveTranslations($service->id, $translations);

        // 关联工作组
        if (!empty($data['group_ids']) && is_array($data['group_ids'])) {
            $service->groups()->attach($data['group_ids']);
        }

        return $this->success(['id' => $service->id], '创建成功');
    }

    /**
     * 更新客服
     */
    public function update(int $id): Response
    {
        $service = ServiceModel::find($id);
        if (!$service) {
            return $this->error('客服不存在', 404);
        }

        $data = input('post.');

        // 更新翻译
        if (isset($data['translations']) && is_array($data['translations'])) {
            $this->saveTranslations($id, $data['translations']);
            // 同步中文名称到name字段（优先 zh-tw，其次 zh-cn）
            $primaryName = $data['translations']['zh-tw']['name'] ?? $data['translations']['zh-cn']['name'] ?? '';
            if (!empty($primaryName)) {
                $service->name = $primaryName;
            }
        }

        if (array_key_exists('admin_id', $data)) {
            $service->admin_id = $data['admin_id'] ?: null;
        }
        if (isset($data['avatar'])) {
            $service->avatar = $data['avatar'];
        }
        if (isset($data['email'])) {
            $service->email = $data['email'];
        }
        if (isset($data['phone'])) {
            $service->phone = $data['phone'];
        }
        if (isset($data['max_sessions'])) {
            $service->max_sessions = $data['max_sessions'];
        }
        if (isset($data['is_enabled'])) {
            $service->is_enabled = $data['is_enabled'];
        }
        $service->save();

        // 更新工作组关联
        if (isset($data['group_ids']) && is_array($data['group_ids'])) {
            $service->groups()->detach();
            if (!empty($data['group_ids'])) {
                $service->groups()->attach($data['group_ids']);
            }
        }

        return $this->success([], '更新成功');
    }

    /**
     * 删除客服
     */
    public function delete(int $id): Response
    {
        $service = ServiceModel::find($id);
        if (!$service) {
            return $this->error('客服不存在', 404);
        }

        // 删除翻译
        CustomerServiceTranslation::where('service_id', $id)->delete();
        // 解除工作组关联
        $service->groups()->detach();
        $service->delete();

        return $this->success([], '删除成功');
    }

    /**
     * 切换状态
     */
    public function toggleStatus(int $id): Response
    {
        $service = ServiceModel::find($id);
        if (!$service) {
            return $this->error('客服不存在', 404);
        }

        $status = input('post.status', 0);
        $service->status = $status;
        if ($status == ServiceModel::STATUS_ONLINE) {
            $service->last_online_at = date('Y-m-d H:i:s');
        }
        $service->save();

        return $this->success([], '状态更新成功');
    }

    /**
     * 获取所有客服（用于下拉选择）
     */
    public function all(): Response
    {
        $query = ServiceModel::where('is_enabled', 1);

        // 筛选状态
        $status = input('status', '');
        if ($status !== '') {
            $query->where('status', (int)$status);
        }

        $list = $query->field('id, name, avatar, status, current_sessions, max_sessions')
            ->order('id', 'asc')
            ->select()
            ->toArray();

        return $this->success($list);
    }

    /**
     * 获取工作组列表
     */
    public function groups(): Response
    {
        $list = ServiceGroup::where('is_enabled', 1)
            ->order('sort', 'asc')
            ->order('id', 'asc')
            ->select()
            ->toArray();

        return $this->success($list);
    }

    /**
     * 保存翻译数据
     * @param int $serviceId 客服ID
     * @param array $translations 翻译数据 ['zh-cn' => ['name' => '...'], 'en' => ['name' => '...']]
     */
    protected function saveTranslations(int $serviceId, array $translations): void
    {
        foreach ($translations as $locale => $data) {
            if (empty($data['name'])) {
                continue;
            }

            $translation = CustomerServiceTranslation::where('service_id', $serviceId)
                ->where('locale', $locale)
                ->find();

            if ($translation) {
                $translation->name = $data['name'];
                $translation->save();
            } else {
                $newTranslation = new CustomerServiceTranslation();
                $newTranslation->service_id = $serviceId;
                $newTranslation->locale = $locale;
                $newTranslation->name = $data['name'];
                $newTranslation->save();
            }
        }
    }
}
