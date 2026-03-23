<?php
declare(strict_types=1);

namespace app\admin\controller;

use think\Response;
use app\common\model\CreditApplication as ApplicationModel;
use app\common\model\UserCreditLimit;
use app\common\model\CreditLimitLog;
use app\common\model\CreditReviewLog;
use app\common\helper\UrlHelper;

/**
 * 信用申请审核控制器
 */
class CreditApplication extends Base
{
    /**
     * 申请列表
     */
    public function index(): Response
    {
        [$page, $pageSize] = $this->getPageParams();

        $query = ApplicationModel::order('id', 'desc');

        // 搜索条件
        $keyword = input('keyword', '');
        $status = input('status', '');
        $idType = input('id_type', '');
        $startDate = input('start_date', '');
        $endDate = input('end_date', '');

        if ($keyword) {
            $query->where(function ($q) use ($keyword) {
                $q->where('application_no', 'like', "%{$keyword}%")
                  ->whereOr('full_name', 'like', "%{$keyword}%")
                  ->whereOr('id_number', 'like', "%{$keyword}%")
                  ->whereOr('phone', 'like', "%{$keyword}%")
                  ->whereOr('email', 'like', "%{$keyword}%");
            });
        }

        if ($status !== '') {
            $query->where('status', (int)$status);
        }

        if ($idType) {
            $query->where('id_type', $idType);
        }

        if ($startDate) {
            $query->where('created_at', '>=', $startDate . ' 00:00:00');
        }
        if ($endDate) {
            $query->where('created_at', '<=', $endDate . ' 23:59:59');
        }

        $total = (clone $query)->count();
        $list = $query->page($page, $pageSize)->select();

        $result = [];
        foreach ($list as $item) {
            $data = $item->toArray();
            // 加载用户信息
            try {
                $data['user'] = $item->user ? [
                    'id' => $item->user->id,
                    'nickname' => $item->user->nickname,
                    'email' => $item->user->email,
                ] : null;
            } catch (\Exception $e) {
                $data['user'] = null;
            }
            // 隐藏敏感字段
            unset($data['card_number_encrypted'], $data['card_expiry_encrypted']);
            $result[] = $data;
        }

        return $this->paginate($result, $total, $page, $pageSize);
    }

    /**
     * 申请详情
     */
    public function read(int $id): Response
    {
        $application = ApplicationModel::find($id);

        if (!$application) {
            return $this->error('申请不存在', 404);
        }

        $data = $application->toArray();

        // 加载用户信息
        try {
            $data['user'] = $application->user ? [
                'id' => $application->user->id,
                'uuid' => $application->user->uuid,
                'nickname' => $application->user->nickname,
                'email' => $application->user->email,
                'phone' => $application->user->phone,
                'created_at' => $application->user->created_at,
            ] : null;
        } catch (\Exception $e) {
            $data['user'] = null;
        }

        // 获取审核记录
        $data['review_logs'] = CreditReviewLog::where('application_id', $id)
            ->order('created_at', 'desc')
            ->select()
            ->toArray();

        // 加载关联的信用卡信息（用于显示完整卡号）
        if (!empty($data['user_card_id'])) {
            $userCard = \app\common\model\UserCard::find($data['user_card_id']);
            if ($userCard) {
                $data['card_full_number'] = $userCard->card_number; // 完整卡号
                $data['card_expiry'] = sprintf('%02d/%d', $userCard->expiry_month, $userCard->expiry_year);
                $data['card_status'] = $userCard->status; // 卡片状态：1=active
            }
        }

        // 隐藏敏感信息（只保留部分可见）
        unset($data['card_number_encrypted'], $data['card_expiry_encrypted']);

        // 转换图片路径为完整 URL
        if (!empty($data['id_front_image'])) {
            $data['id_front_image'] = UrlHelper::getFullUrl($data['id_front_image']);
        }
        if (!empty($data['id_back_image'])) {
            $data['id_back_image'] = UrlHelper::getFullUrl($data['id_back_image']);
        }
        if (!empty($data['selfie_image'])) {
            $data['selfie_image'] = UrlHelper::getFullUrl($data['selfie_image']);
        }
        if (!empty($data['income_proof'])) {
            $data['income_proof'] = UrlHelper::getFullUrl($data['income_proof']);
        }
        // 处理账单图片（JSON 数组）
        if (!empty($data['statement_images'])) {
            $statementImages = is_string($data['statement_images'])
                ? json_decode($data['statement_images'], true)
                : $data['statement_images'];
            if (is_array($statementImages)) {
                $data['statement_images'] = array_map(function($img) {
                    return UrlHelper::getFullUrl($img);
                }, $statementImages);
            }
        }

        return $this->success($data);
    }

    /**
     * 审核通过
     */
    public function approve(int $id): Response
    {
        $application = ApplicationModel::find($id);

        if (!$application) {
            return $this->error('申请不存在', 404);
        }

        if (!in_array($application->status, [ApplicationModel::STATUS_PENDING, ApplicationModel::STATUS_REVIEWING])) {
            return $this->error('当前状态不可审核');
        }

        // 兼容两种参数名
        $approvedLimit = (float)input('approved_limit', 0) ?: (float)input('approved_amount', 0);
        $creditLevel = (int)input('credit_level', 1);
        $remark = input('remark', '');

        if ($approvedLimit <= 0) {
            return $this->error('请输入有效的额度');
        }

        if ($creditLevel < 1 || $creditLevel > 5) {
            return $this->error('信用等级必须在1-5之间');
        }

        // 开始事务
        ApplicationModel::startTrans();
        try {
            // 更新申请状态
            $application->status = ApplicationModel::STATUS_APPROVED;
            $application->approved_limit = $approvedLimit;
            $application->reviewer_id = $this->adminId;
            $application->reviewed_at = date('Y-m-d H:i:s');
            $application->save();

            // 创建用户额度
            $creditLimit = new UserCreditLimit();
            $creditLimit->user_id = $application->user_id;
            $creditLimit->application_id = $application->id;
            $creditLimit->total_limit = $approvedLimit;
            $creditLimit->used_limit = 0;
            $creditLimit->frozen_limit = 0;
            $creditLimit->credit_level = $creditLevel;
            $creditLimit->status = UserCreditLimit::STATUS_ACTIVE;
            $creditLimit->expires_at = date('Y-m-d H:i:s', strtotime('+1 year'));
            $creditLimit->save();

            // 记录额度变动
            CreditLimitLog::log(
                $application->user_id,
                CreditLimitLog::TYPE_GRANT,
                $approvedLimit,
                0,
                $approvedLimit,
                0,
                0,
                $application->id,
                'credit_application',
                $remark ?: '信用申请审核通过',
                $this->adminId
            );

            // 记录审核日志
            CreditReviewLog::log(
                $application->id,
                $this->adminId,
                CreditReviewLog::ACTION_APPROVE,
                $remark ?: '审核通过',
                [
                    'approved_limit' => $approvedLimit,
                    'credit_level' => $creditLevel,
                ]
            );

            ApplicationModel::commit();

            return $this->success([], '审核通过');
        } catch (\Exception $e) {
            ApplicationModel::rollback();
            return $this->error('操作失败：' . $e->getMessage());
        }
    }

    /**
     * 审核拒绝
     */
    public function reject(int $id): Response
    {
        $application = ApplicationModel::find($id);

        if (!$application) {
            return $this->error('申请不存在', 404);
        }

        if (!in_array($application->status, [ApplicationModel::STATUS_PENDING, ApplicationModel::STATUS_REVIEWING])) {
            return $this->error('当前状态不可审核');
        }

        // 兼容两种参数名
        $reason = input('reason', '') ?: input('reject_reason', '');
        if (empty($reason)) {
            return $this->error('请输入拒绝原因');
        }

        $application->status = ApplicationModel::STATUS_REJECTED;
        $application->reject_reason = $reason;
        $application->reviewer_id = $this->adminId;
        $application->reviewed_at = date('Y-m-d H:i:s');
        $application->save();

        // 记录审核日志
        CreditReviewLog::log(
            $application->id,
            $this->adminId,
            CreditReviewLog::ACTION_REJECT,
            $reason
        );

        return $this->success([], '已拒绝');
    }

    /**
     * 要求补充资料
     */
    public function supplement(int $id): Response
    {
        $application = ApplicationModel::find($id);

        if (!$application) {
            return $this->error('申请不存在', 404);
        }

        if (!in_array($application->status, [ApplicationModel::STATUS_PENDING, ApplicationModel::STATUS_REVIEWING])) {
            return $this->error('当前状态不可操作');
        }

        // 兼容两种参数名
        $request = input('request', '') ?: input('supplement_reason', '');
        if (empty($request)) {
            return $this->error('请输入需要补充的资料说明');
        }

        $application->status = ApplicationModel::STATUS_SUPPLEMENT;
        $application->supplement_request = $request;
        $application->save();

        // 记录审核日志
        CreditReviewLog::log(
            $application->id,
            $this->adminId,
            CreditReviewLog::ACTION_SUPPLEMENT,
            $request
        );

        return $this->success([], '已通知用户补充资料');
    }

    /**
     * 开始审核（标记为审核中）
     */
    public function startReview(int $id): Response
    {
        $application = ApplicationModel::find($id);

        if (!$application) {
            return $this->error('申请不存在', 404);
        }

        if ($application->status !== ApplicationModel::STATUS_PENDING) {
            return $this->error('只能对待审核状态的申请进行此操作');
        }

        $application->status = ApplicationModel::STATUS_REVIEWING;
        $application->reviewer_id = $this->adminId;
        $application->save();

        // 记录审核日志
        CreditReviewLog::log(
            $application->id,
            $this->adminId,
            CreditReviewLog::ACTION_START_REVIEW,
            '开始审核'
        );

        return $this->success([], '已开始审核');
    }

    /**
     * 统计数据
     */
    public function statistics(): Response
    {
        $pending = ApplicationModel::where('status', ApplicationModel::STATUS_PENDING)->count();
        $reviewing = ApplicationModel::where('status', ApplicationModel::STATUS_REVIEWING)->count();
        $supplement = ApplicationModel::where('status', ApplicationModel::STATUS_SUPPLEMENT)->count();
        $approved = ApplicationModel::where('status', ApplicationModel::STATUS_APPROVED)->count();
        $rejected = ApplicationModel::where('status', ApplicationModel::STATUS_REJECTED)->count();

        // 今日数据
        $today = date('Y-m-d');
        $todayNew = ApplicationModel::where('created_at', '>=', $today . ' 00:00:00')
            ->where('created_at', '<=', $today . ' 23:59:59')
            ->count();
        $todayApproved = ApplicationModel::where('reviewed_at', '>=', $today . ' 00:00:00')
            ->where('reviewed_at', '<=', $today . ' 23:59:59')
            ->where('status', ApplicationModel::STATUS_APPROVED)
            ->count();

        // 本月数据
        $monthStart = date('Y-m-01');
        $monthNew = ApplicationModel::where('created_at', '>=', $monthStart . ' 00:00:00')->count();
        $monthApproved = ApplicationModel::where('reviewed_at', '>=', $monthStart . ' 00:00:00')
            ->where('status', ApplicationModel::STATUS_APPROVED)
            ->count();

        // 审批通过率
        $totalProcessed = $approved + $rejected;
        $approvalRate = $totalProcessed > 0 ? round($approved / $totalProcessed * 100, 1) : 0;

        return $this->success([
            'pending' => $pending,
            'reviewing' => $reviewing,
            'supplement' => $supplement,
            'approved' => $approved,
            'rejected' => $rejected,
            'today_new' => $todayNew,
            'today_approved' => $todayApproved,
            'month_new' => $monthNew,
            'month_approved' => $monthApproved,
            'approval_rate' => $approvalRate,
        ]);
    }

    /**
     * 获取状态列表
     */
    public function statusList(): Response
    {
        return $this->success([
            ['value' => ApplicationModel::STATUS_PENDING, 'label' => '待审核'],
            ['value' => ApplicationModel::STATUS_REVIEWING, 'label' => '审核中'],
            ['value' => ApplicationModel::STATUS_SUPPLEMENT, 'label' => '待补充'],
            ['value' => ApplicationModel::STATUS_APPROVED, 'label' => '已通过'],
            ['value' => ApplicationModel::STATUS_REJECTED, 'label' => '已拒绝'],
        ]);
    }

    /**
     * 获取证件类型列表
     */
    public function idTypeList(): Response
    {
        return $this->success([
            ['value' => ApplicationModel::ID_TYPE_PASSPORT, 'label' => '护照'],
            ['value' => ApplicationModel::ID_TYPE_ID_CARD, 'label' => '身份证'],
            ['value' => ApplicationModel::ID_TYPE_DRIVER_LICENSE, 'label' => '驾照'],
        ]);
    }
}
