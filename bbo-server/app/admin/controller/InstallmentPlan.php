<?php
declare(strict_types=1);

namespace app\admin\controller;

use think\Response;
use app\common\model\InstallmentPlan as PlanModel;
use app\common\model\InstallmentPlanTranslation;

/**
 * 分期方案管理控制器
 */
class InstallmentPlan extends Base
{
    /**
     * 方案列表
     */
    public function index(): Response
    {
        [$page, $pageSize] = $this->getPageParams();

        $query = PlanModel::order('sort', 'desc')->order('id', 'asc');

        $status = input('status', '');

        if ($status !== '') {
            $query->where('status', (int)$status);
        }

        $total = (clone $query)->count();
        $list = $query->page($page, $pageSize)->select();

        $result = [];
        foreach ($list as $item) {
            $data = $item->toArray();
            // 获取翻译
            $data['translations'] = InstallmentPlanTranslation::where('plan_id', $item->id)
                ->select()
                ->toArray();
            $result[] = $data;
        }

        return $this->paginate($result, $total, $page, $pageSize);
    }

    /**
     * 方案详情
     */
    public function read(int $id): Response
    {
        $plan = PlanModel::find($id);

        if (!$plan) {
            return $this->error('方案不存在', 404);
        }

        $data = $plan->toArray();
        $data['translations'] = InstallmentPlanTranslation::where('plan_id', $id)
            ->select()
            ->toArray();

        return $this->success($data);
    }

    /**
     * 创建方案
     */
    public function save(): Response
    {
        $data = input();

        // 验证必填字段
        $required = ['periods', 'interest_rate', 'min_amount', 'max_amount'];
        foreach ($required as $field) {
            if (!isset($data[$field])) {
                return $this->error("字段 '{$field}' 是必填的");
            }
        }

        $plan = new PlanModel();
        $plan->periods = (int)$data['periods'];
        $plan->interest_rate = (float)$data['interest_rate'];
        $plan->fee_rate = (float)($data['fee_rate'] ?? 0);
        $plan->min_amount = (float)$data['min_amount'];
        $plan->max_amount = (float)$data['max_amount'];
        $plan->min_credit_level = (int)($data['min_credit_level'] ?? 1);
        $plan->status = (int)($data['status'] ?? 1);
        $plan->sort = (int)($data['sort'] ?? 0);
        $plan->save();

        // 保存翻译
        if (!empty($data['translations'])) {
            foreach ($data['translations'] as $trans) {
                if (!empty($trans['locale']) && !empty($trans['name'])) {
                    $translation = new InstallmentPlanTranslation();
                    $translation->plan_id = $plan->id;
                    $translation->locale = $trans['locale'];
                    $translation->name = $trans['name'];
                    $translation->description = $trans['description'] ?? null;
                    $translation->save();
                }
            }
        }

        return $this->success(['id' => $plan->id], '创建成功');
    }

    /**
     * 更新方案
     */
    public function update(int $id): Response
    {
        $plan = PlanModel::find($id);

        if (!$plan) {
            return $this->error('方案不存在', 404);
        }

        $data = input();

        if (isset($data['periods'])) {
            $plan->periods = (int)$data['periods'];
        }
        if (isset($data['interest_rate'])) {
            $plan->interest_rate = (float)$data['interest_rate'];
        }
        if (isset($data['fee_rate'])) {
            $plan->fee_rate = (float)$data['fee_rate'];
        }
        if (isset($data['min_amount'])) {
            $plan->min_amount = (float)$data['min_amount'];
        }
        if (isset($data['max_amount'])) {
            $plan->max_amount = (float)$data['max_amount'];
        }
        if (isset($data['min_credit_level'])) {
            $plan->min_credit_level = (int)$data['min_credit_level'];
        }
        if (isset($data['status'])) {
            $plan->status = (int)$data['status'];
        }
        if (isset($data['sort'])) {
            $plan->sort = (int)$data['sort'];
        }

        $plan->save();

        // 更新翻译
        if (!empty($data['translations'])) {
            // 删除旧翻译
            InstallmentPlanTranslation::where('plan_id', $id)->delete();

            // 创建新翻译
            foreach ($data['translations'] as $trans) {
                if (!empty($trans['locale']) && !empty($trans['name'])) {
                    $translation = new InstallmentPlanTranslation();
                    $translation->plan_id = $plan->id;
                    $translation->locale = $trans['locale'];
                    $translation->name = $trans['name'];
                    $translation->description = $trans['description'] ?? null;
                    $translation->save();
                }
            }
        }

        return $this->success([], '更新成功');
    }

    /**
     * 删除方案
     */
    public function delete(int $id): Response
    {
        $plan = PlanModel::find($id);

        if (!$plan) {
            return $this->error('方案不存在', 404);
        }

        // 检查是否有关联的订单
        $orderCount = \app\common\model\InstallmentOrder::where('plan_id', $id)->count();
        if ($orderCount > 0) {
            return $this->error('该方案已有关联订单，无法删除');
        }

        // 删除翻译
        InstallmentPlanTranslation::where('plan_id', $id)->delete();

        // 删除方案
        $plan->delete();

        return $this->success([], '删除成功');
    }

    /**
     * 切换状态
     */
    public function toggleStatus(int $id): Response
    {
        $plan = PlanModel::find($id);

        if (!$plan) {
            return $this->error('方案不存在', 404);
        }

        $plan->status = $plan->status === 1 ? 0 : 1;
        $plan->save();

        return $this->success([], $plan->status ? '已启用' : '已禁用');
    }
}
